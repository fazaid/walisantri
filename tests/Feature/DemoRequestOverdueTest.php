<?php

namespace Tests\Feature;

use App\Models\DemoRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemoRequestOverdueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tes ini fokus ke logika SLA, bukan notifikasi. DemoRequestObserver::created()
        // men-dispatch WA (queue sync di testing → kirim sungguhan), jadi di-fake.
        Queue::fake();

        // Bekukan waktu ke hari kerja (Rabu) supaya slaCutoff() deterministik.
        $this->travelTo(SupportCarbon::parse('2026-07-22 10:00:00'));
    }

    /**
     * Buat permintaan demo dengan created_at spesifik. created_at bukan bagian
     * dari $fillable, jadi diset manual — Eloquent tidak menimpanya kalau sudah
     * "dirty" sebelum save.
     */
    private function demoRequestAt(Carbon $createdAt, ?Carbon $contactedAt = null): DemoRequest
    {
        $record = new DemoRequest([
            'nama_pesantren' => 'Pesantren Uji',
            'nama_kontak' => 'PIC Uji',
            'email' => 'uji@example.com',
            'no_hp' => '08123456789',
            'contacted_at' => $contactedAt,
        ]);
        $record->created_at = $createdAt;
        $record->save();

        return $record->refresh();
    }

    public function test_tepat_di_batas_sla_belum_overdue(): void
    {
        // created_at == slaCutoff → tepat SLA_BUSINESS_DAYS hari kerja: masih on-time.
        $cutoff = DemoRequest::slaCutoff();
        $record = $this->demoRequestAt($cutoff->copy());

        $this->assertFalse($record->isOverdue());
        $this->assertFalse(DemoRequest::query()->overdue()->whereKey($record->id)->exists());
    }

    public function test_sedetik_melewati_batas_sla_sudah_overdue(): void
    {
        $cutoff = DemoRequest::slaCutoff();
        $record = $this->demoRequestAt($cutoff->copy()->subSecond());

        $this->assertTrue($record->isOverdue());
        $this->assertTrue(DemoRequest::query()->overdue()->whereKey($record->id)->exists());
    }

    public function test_sedetik_sebelum_batas_sla_belum_overdue(): void
    {
        $cutoff = DemoRequest::slaCutoff();
        $record = $this->demoRequestAt($cutoff->copy()->addSecond());

        $this->assertFalse($record->isOverdue());
        $this->assertFalse(DemoRequest::query()->overdue()->whereKey($record->id)->exists());
    }

    public function test_sudah_dihubungi_tidak_pernah_overdue(): void
    {
        // Jauh melewati batas, tapi sudah dihubungi → tidak boleh overdue.
        $cutoff = DemoRequest::slaCutoff();
        $record = $this->demoRequestAt($cutoff->copy()->subWeek(), contactedAt: now());

        $this->assertFalse($record->isOverdue());
        $this->assertFalse(DemoRequest::query()->overdue()->whereKey($record->id)->exists());
    }

    public function test_scope_dan_isoverdue_selalu_konsisten(): void
    {
        $cutoff = DemoRequest::slaCutoff();

        $overdue = $this->demoRequestAt($cutoff->copy()->subSecond());
        $onBoundary = $this->demoRequestAt($cutoff->copy());
        $fresh = $this->demoRequestAt($cutoff->copy()->addSecond());
        $contacted = $this->demoRequestAt($cutoff->copy()->subWeek(), contactedAt: now());

        $overdueIds = DemoRequest::query()->overdue()->pluck('id');

        foreach ([$overdue, $onBoundary, $fresh, $contacted] as $record) {
            $this->assertSame(
                $record->isOverdue(),
                $overdueIds->contains($record->id),
                "isOverdue() dan scopeOverdue() harus sepakat untuk record #{$record->id}",
            );
        }

        // Hanya satu record (yang benar-benar melewati batas) yang overdue.
        $this->assertEquals([$overdue->id], $overdueIds->all());
    }
}
