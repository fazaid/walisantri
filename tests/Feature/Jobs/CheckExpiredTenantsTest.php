<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckExpiredTenants;
use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Pesantren;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CheckExpiredTenantsTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(array $override = []): Pesantren
    {
        return Pesantren::create(array_merge([
            'nama_pesantren' => 'Pesantren Expired Test',
            'slug' => 'pesantren-expired-'.uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'active',
            'expired_at' => now()->subDay(),
        ], $override));
    }

    private function makeAdmin(Pesantren $pesantren, ?string $phone = '081234567890'): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'pesantren_id' => $pesantren->id,
            'name' => "Admin Expired Test {$counter}",
            'email' => "admin.expired.{$counter}@wa.test",
            'phone_number' => $phone,
            'password' => bcrypt('password'),
            'role' => 'admin_pesantren',
        ]);
    }

    public function test_status_berubah_ke_expired_dan_kirim_notifikasi_wa(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren);

        (new CheckExpiredTenants)->handle();

        $this->assertSame('expired', $pesantren->fresh()->status_berlangganan);

        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => $job->phoneNumber === '081234567890'
            && str_contains($job->message, $pesantren->nama_pesantren)
        );
    }

    public function test_tidak_kirim_notifikasi_untuk_tenant_yang_sudah_expired_sebelumnya(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren([
            'status_berlangganan' => 'expired',
            'expired_at' => now()->subDay(),
        ]);
        $this->makeAdmin($pesantren);

        (new CheckExpiredTenants)->handle();

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_tidak_kirim_jika_admin_tanpa_phone_number(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren, null);

        (new CheckExpiredTenants)->handle();

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_tidak_kirim_jika_toggle_dimatikan_super_admin(): void
    {
        Queue::fake();

        WhatsAppSetting::set('notif_trial_habis_enabled', false);

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren);

        (new CheckExpiredTenants)->handle();

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_pesan_menggunakan_template_kustom_dari_pengaturan(): void
    {
        Queue::fake();

        WhatsAppMessageTemplate::set(
            'notif_trial_habis',
            'Halo {nama_pesantren}, expired {tanggal_expired}, bayar di {link_billing}.',
        );

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren);

        (new CheckExpiredTenants)->handle();

        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => $job->message === "Halo {$pesantren->nama_pesantren}, expired {$pesantren->expired_at->locale('id')->translatedFormat('d F Y')}, bayar di ".url('/admin/billing-page').'.'
        );
    }

    public function test_expired_ke_suspended_setelah_grace_period_tidak_kirim_notifikasi(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren([
            'status_berlangganan' => 'expired',
            'expired_at' => now()->subDays(8),
        ]);
        $this->makeAdmin($pesantren);

        (new CheckExpiredTenants)->handle();

        $this->assertSame('suspended', $pesantren->fresh()->status_berlangganan);
        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }
}
