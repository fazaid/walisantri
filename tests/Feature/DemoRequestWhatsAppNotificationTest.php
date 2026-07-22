<?php

namespace Tests\Feature;

use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\DemoRequest;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemoRequestWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeDemoRequest(array $override = []): DemoRequest
    {
        return DemoRequest::create(array_merge([
            'nama_pesantren' => 'Pesantren Uji',
            'nama_kontak' => 'Ustadz Budi',
            'email' => 'budi'.uniqid().'@example.com',
            'no_hp' => '081234567890',
            'kota' => 'Bandung',
            'jumlah_santri' => '50',
        ], $override));
    }

    public function test_dispatch_notifikasi_terima_kasih_saat_form_demo_disubmit(): void
    {
        Queue::fake();

        $demo = $this->makeDemoRequest();

        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => $job->phoneNumber === '081234567890'
            && str_contains($job->message, 'Ustadz Budi')
            && str_contains($job->message, 'Pesantren Uji')
        );
    }

    public function test_pesan_default_memuat_link_grup_whatsapp(): void
    {
        Queue::fake();

        $this->makeDemoRequest();

        Queue::assertPushed(
            KirimNotifikasiWhatsapp::class,
            fn ($job) => str_contains($job->message, 'chat.whatsapp.com'),
        );
    }

    public function test_tidak_dispatch_jika_toggle_dimatikan_super_admin(): void
    {
        Queue::fake();

        WhatsAppSetting::set('notif_demo_terima_kasih_enabled', false);

        $this->makeDemoRequest();

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_tidak_dispatch_jika_no_hp_kosong(): void
    {
        Queue::fake();

        $this->makeDemoRequest(['no_hp' => '']);

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_tetap_dispatch_untuk_submission_duplikat(): void
    {
        Queue::fake();

        $first = $this->makeDemoRequest(['no_hp' => '081299998888']);
        $second = $this->makeDemoRequest(['no_hp' => '081299998888']);

        // Deteksi duplikat tetap berjalan (observer creating), tapi tidak memblokir kirim WA.
        $this->assertSame($first->id, $second->duplicate_of_id);
        Queue::assertPushed(KirimNotifikasiWhatsapp::class, 2);
    }

    public function test_pesan_menggunakan_template_kustom_dari_pengaturan(): void
    {
        Queue::fake();

        WhatsAppMessageTemplate::set(
            'notif_demo_terima_kasih',
            'Halo {nama_kontak} dari {nama_pesantren}, gabung grup: https://chat.whatsapp.com/CUSTOM',
        );

        $this->makeDemoRequest(['nama_kontak' => 'Kyai Ali', 'nama_pesantren' => 'PP Custom']);

        Queue::assertPushed(
            KirimNotifikasiWhatsapp::class,
            fn ($job) => $job->message === 'Halo Kyai Ali dari PP Custom, gabung grup: https://chat.whatsapp.com/CUSTOM',
        );
    }
}
