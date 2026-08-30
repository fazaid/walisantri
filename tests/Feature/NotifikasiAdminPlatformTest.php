<?php

namespace Tests\Feature;

use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\DemoRequest;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Pesantren;
use App\Models\PlatformContactSetting;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use App\Services\UpgradeOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotifikasiAdminPlatformTest extends TestCase
{
    use RefreshDatabase;

    private const NOMOR_ADMIN = '6281399096658';

    protected function setUp(): void
    {
        parent::setUp();

        // Ucapan terima kasih ke pendaftar demo dimatikan di seluruh kelas ini supaya
        // job yang terhitung hanya milik alert internal, bukan milik notifikasi lama.
        WhatsAppSetting::set('notif_demo_terima_kasih_enabled', false);
    }

    private function aktifkanAlertAdmin(?string $nomor = self::NOMOR_ADMIN): void
    {
        WhatsAppSetting::set('notif_admin_platform_enabled', true);
        PlatformContactSetting::set('admin_whatsapp', $nomor);
    }

    private function makeDemoRequest(array $override = []): DemoRequest
    {
        return DemoRequest::create(array_merge([
            'nama_pesantren' => 'Pesantren Alert',
            'nama_kontak' => 'Ustadz Fulan',
            'email' => 'fulan'.uniqid().'@example.com',
            'no_hp' => '081234567890',
            'kota' => 'Bandung',
            'jumlah_santri' => '120',
        ], $override));
    }

    private function makePesantren(): Pesantren
    {
        return Pesantren::create([
            'nama_pesantren' => 'Pesantren Alert Order',
            'slug' => 'pesantren-alert-'.uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addMonth(),
        ]);
    }

    /**
     * @return array{order: Order, invoice: Invoice}
     */
    private function buatOrder(): array
    {
        return app(UpgradeOrderService::class)->createOrder(
            pesantren: $this->makePesantren(),
            paketTarget: 'rintisan',
            durasibulan: 1,
            maxSantriKuota: 100,
        );
    }

    // --- Lead demo baru -----------------------------------------------------

    public function test_alert_demo_baru_dikirim_ke_nomor_admin_platform(): void
    {
        Queue::fake();
        $this->aktifkanAlertAdmin();

        $this->makeDemoRequest();

        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => $job->phoneNumber === self::NOMOR_ADMIN
            && str_contains($job->message, 'Pesantren Alert')
            && str_contains($job->message, 'Ustadz Fulan')
            && str_contains($job->message, '081234567890')
        );
    }

    public function test_alert_demo_memakai_template_kustom(): void
    {
        Queue::fake();
        $this->aktifkanAlertAdmin();

        WhatsAppMessageTemplate::set('notif_admin_demo_baru', 'Lead: {nama_pesantren} / {kota}');

        $this->makeDemoRequest(['nama_pesantren' => 'PP Kustom', 'kota' => 'Garut']);

        Queue::assertPushed(
            KirimNotifikasiWhatsapp::class,
            fn ($job) => $job->message === 'Lead: PP Kustom / Garut',
        );
    }

    public function test_kolom_opsional_kosong_diganti_strip(): void
    {
        Queue::fake();
        $this->aktifkanAlertAdmin();

        $this->makeDemoRequest(['kota' => null, 'jumlah_santri' => null]);

        Queue::assertPushed(
            KirimNotifikasiWhatsapp::class,
            fn ($job) => str_contains($job->message, 'Kota      : -'),
        );
    }

    // --- Pesanan upgrade ----------------------------------------------------

    public function test_alert_order_baru_dikirim_saat_order_dibuat(): void
    {
        Queue::fake();
        $this->aktifkanAlertAdmin();

        $hasil = $this->buatOrder();

        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => $job->phoneNumber === self::NOMOR_ADMIN
            && str_contains($job->message, $hasil['order']->nomor_order)
            && str_contains($job->message, 'Pesantren Alert Order')
        );
    }

    public function test_alert_bukti_transfer_dikirim_saat_bukti_diupload(): void
    {
        Storage::fake('local');
        Queue::fake();
        $this->aktifkanAlertAdmin();

        $hasil = $this->buatOrder();

        app(UpgradeOrderService::class)->uploadBuktiTransfer(
            $hasil['invoice'],
            UploadedFile::fake()->image('bukti.jpg'),
        );

        // Dua job: satu saat order dibuat, satu saat bukti diupload. Hanya template
        // bukti yang memuat nomor invoice, jadi itulah pembedanya.
        Queue::assertPushed(KirimNotifikasiWhatsapp::class, 2);
        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => $job->phoneNumber === self::NOMOR_ADMIN
            && str_contains($job->message, $hasil['invoice']->nomor_invoice)
            && str_contains($job->message, 'perlu konfirmasi')
        );
    }

    // --- Kill-switch & nomor kosong ----------------------------------------

    public function test_tidak_dispatch_saat_kill_switch_mati(): void
    {
        Storage::fake('local');
        Queue::fake();

        // Kondisi default production: toggle mati walau nomor sudah diisi.
        PlatformContactSetting::set('admin_whatsapp', self::NOMOR_ADMIN);

        $this->makeDemoRequest();
        $hasil = $this->buatOrder();
        app(UpgradeOrderService::class)->uploadBuktiTransfer(
            $hasil['invoice'],
            UploadedFile::fake()->image('bukti.jpg'),
        );

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_kill_switch_default_mati_tanpa_pengaturan_apa_pun(): void
    {
        Queue::fake();

        PlatformContactSetting::set('admin_whatsapp', self::NOMOR_ADMIN);

        $this->assertFalse(WhatsAppSetting::get('notif_admin_platform_enabled', false));

        $this->makeDemoRequest();

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_tidak_dispatch_saat_nomor_admin_kosong(): void
    {
        Storage::fake('local');
        Queue::fake();

        $this->aktifkanAlertAdmin(nomor: null);

        $this->makeDemoRequest();
        $hasil = $this->buatOrder();
        app(UpgradeOrderService::class)->uploadBuktiTransfer(
            $hasil['invoice'],
            UploadedFile::fake()->image('bukti.jpg'),
        );

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_alert_admin_tidak_mengganggu_notifikasi_terima_kasih_pendaftar(): void
    {
        Queue::fake();

        WhatsAppSetting::set('notif_demo_terima_kasih_enabled', true);
        $this->aktifkanAlertAdmin();

        $this->makeDemoRequest(['no_hp' => '081200001111']);

        // Dua job berbeda tujuan: pendaftar demo dan admin platform.
        Queue::assertPushed(KirimNotifikasiWhatsapp::class, 2);
        Queue::assertPushed(
            KirimNotifikasiWhatsapp::class,
            fn ($job) => $job->phoneNumber === '081200001111',
        );
        Queue::assertPushed(
            KirimNotifikasiWhatsapp::class,
            fn ($job) => $job->phoneNumber === self::NOMOR_ADMIN,
        );
    }
}
