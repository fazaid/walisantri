<?php

namespace Tests\Feature\Jobs;

use App\Jobs\PurgeAuditLogs;
use App\Models\ActivityLog;
use App\Models\Pesantren;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Retensi §10.3: log operasional 2 tahun, jejak billing 5 tahun.
 *
 * Job ini tidak pernah punya tes sama sekali, dan celahnya baru ketahuan lewat
 * audit: ketiga event `order.*` — satu-satunya bukti alur pembayaran upgrade —
 * ikut terhapus di tahun kedua karena tidak terdaftar sebagai peristiwa billing.
 */
class PurgeAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(): Pesantren
    {
        return Pesantren::create([
            'nama_pesantren' => 'Pesantren Retensi Test',
            'slug' => 'pesantren-retensi-'.uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addMonth(),
        ]);
    }

    private function makeLog(string $event, int $umurTahun): ActivityLog
    {
        return ActivityLog::create([
            'pesantren_id' => $this->makePesantren()->id,
            'user_id' => null,
            'event' => $event,
            'created_at' => now()->subYears($umurTahun),
        ]);
    }

    public function test_jejak_order_bertahan_sampai_lima_tahun(): void
    {
        $log = $this->makeLog('order.confirmed', 3);

        (new PurgeAuditLogs)->handle();

        $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);
    }

    public function test_jejak_order_lebih_dari_lima_tahun_dihapus(): void
    {
        $log = $this->makeLog('order.confirmed', 6);

        (new PurgeAuditLogs)->handle();

        $this->assertDatabaseMissing('activity_logs', ['id' => $log->id]);
    }

    public function test_ketiga_event_order_diperlakukan_sebagai_billing(): void
    {
        $id = collect(['order.bukti_uploaded', 'order.confirmed', 'order.rejected'])
            ->map(fn (string $event) => $this->makeLog($event, 3)->id);

        (new PurgeAuditLogs)->handle();

        $id->each(fn (int $id) => $this->assertDatabaseHas('activity_logs', ['id' => $id]));
    }

    public function test_log_operasional_dihapus_setelah_dua_tahun(): void
    {
        $log = $this->makeLog('santri.created', 3);

        (new PurgeAuditLogs)->handle();

        $this->assertDatabaseMissing('activity_logs', ['id' => $log->id]);
    }

    public function test_log_yang_masih_muda_tidak_disentuh(): void
    {
        $operasional = $this->makeLog('santri.created', 1);
        $billing = $this->makeLog('order.confirmed', 1);

        (new PurgeAuditLogs)->handle();

        $this->assertDatabaseHas('activity_logs', ['id' => $operasional->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $billing->id]);
    }
}
