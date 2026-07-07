<?php

namespace Tests\Feature\Jobs;

use App\Jobs\KirimNotifikasiWhatsapp;
use App\Jobs\WarnExpiringTenantsWhatsApp;
use App\Models\Pesantren;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WarnExpiringTenantsWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(array $override = []): Pesantren
    {
        return Pesantren::create(array_merge([
            'nama_pesantren' => 'Pesantren WA Test',
            'slug' => 'pesantren-wa-'.uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addDays(3),
        ], $override));
    }

    private function makeAdmin(Pesantren $pesantren, ?string $phone = '081234567890'): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'pesantren_id' => $pesantren->id,
            'name' => "Admin WA Test {$counter}",
            'email' => "admin.wa.{$counter}@wa.test",
            'phone_number' => $phone,
            'password' => bcrypt('password'),
            'role' => 'admin_pesantren',
        ]);
    }

    public function test_dispatch_notifikasi_untuk_pesantren_h3(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren(['expired_at' => now()->addDays(3)->setTime(10, 0)]);
        $this->makeAdmin($pesantren);

        (new WarnExpiringTenantsWhatsApp())->handle();

        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => $job->phoneNumber === '081234567890'
            && str_contains($job->message, $pesantren->nama_pesantren)
        );
    }

    public function test_dispatch_notifikasi_untuk_pesantren_h1(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren(['expired_at' => now()->addDays(1)->setTime(10, 0)]);
        $this->makeAdmin($pesantren);

        (new WarnExpiringTenantsWhatsApp())->handle();

        Queue::assertPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_tidak_dispatch_jika_admin_tanpa_phone_number(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren(['expired_at' => now()->addDays(3)->setTime(10, 0)]);
        $this->makeAdmin($pesantren, null);

        (new WarnExpiringTenantsWhatsApp())->handle();

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_tidak_dispatch_jika_expired_at_di_luar_rentang_warn_days(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren(['expired_at' => now()->addDays(10)]);
        $this->makeAdmin($pesantren);

        (new WarnExpiringTenantsWhatsApp())->handle();

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_tidak_dispatch_untuk_tenant_status_expired(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren([
            'status_berlangganan' => 'expired',
            'expired_at' => now()->addDays(3)->setTime(10, 0),
        ]);
        $this->makeAdmin($pesantren);

        (new WarnExpiringTenantsWhatsApp())->handle();

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_tidak_dispatch_jika_toggle_dimatikan_super_admin(): void
    {
        Queue::fake();

        WhatsAppSetting::set('reminder_expired_enabled', false);

        $pesantren = $this->makePesantren(['expired_at' => now()->addDays(3)->setTime(10, 0)]);
        $this->makeAdmin($pesantren);

        (new WarnExpiringTenantsWhatsApp())->handle();

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_pesan_pakai_template_default_dari_seed_migrasi(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren(['expired_at' => now()->addDays(3)->setTime(10, 0)]);
        $this->makeAdmin($pesantren);

        (new WarnExpiringTenantsWhatsApp())->handle();

        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => str_contains($job->message, $pesantren->nama_pesantren)
            && str_contains($job->message, $pesantren->expired_at->format('d F Y'))
            && str_contains($job->message, url('/admin/billing-page'))
        );
    }

    public function test_pesan_menggunakan_template_kustom_dari_pengaturan(): void
    {
        Queue::fake();

        WhatsAppMessageTemplate::set(
            'reminder_expired',
            'Halo {nama_pesantren}, sisa {sisa_hari} hari, expired {tanggal_expired}, bayar di {link_billing}.',
        );

        $pesantren = $this->makePesantren(['expired_at' => now()->addDays(3)->setTime(10, 0)]);
        $this->makeAdmin($pesantren);

        (new WarnExpiringTenantsWhatsApp())->handle();

        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => $job->message === "Halo {$pesantren->nama_pesantren}, sisa 3 hari, expired {$pesantren->expired_at->format('d F Y')}, bayar di ".url('/admin/billing-page').'.'
        );
    }
}
