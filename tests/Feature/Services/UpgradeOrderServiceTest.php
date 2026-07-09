<?php

namespace Tests\Feature\Services;

use App\Enums\StatusOrder;
use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Order;
use App\Models\Pesantren;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use App\Services\UpgradeOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpgradeOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(array $override = []): Pesantren
    {
        return Pesantren::create(array_merge([
            'nama_pesantren' => 'Pesantren Order Test',
            'slug' => 'pesantren-order-'.uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addMonth(),
        ], $override));
    }

    private function makeAdmin(Pesantren $pesantren, ?string $phone = '081234567890'): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'pesantren_id' => $pesantren->id,
            'name' => "Admin Order Test {$counter}",
            'email' => "admin.order.{$counter}@wa.test",
            'phone_number' => $phone,
            'password' => bcrypt('password'),
            'role' => 'admin_pesantren',
        ]);
    }

    private function makeConfirmer(): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'pesantren_id' => null,
            'name' => "Super Admin Test {$counter}",
            'email' => "super.admin.{$counter}@wa.test",
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
    }

    private function makeOrder(Pesantren $pesantren, array $override = []): Order
    {
        return Order::create(array_merge([
            'pesantren_id' => $pesantren->id,
            'nomor_order' => 'WS-'.uniqid(),
            'paket_target' => 'berkembang',
            'durasi_bulan' => 12,
            'max_santri_kuota_target' => 500,
            'harga_per_bulan' => 100000,
            'harga_total_sebelum_diskon' => 1200000,
            'harga_total' => 1200000,
            'durasi_total_bulan' => 12,
            'status' => StatusOrder::AwaitingConfirmation,
        ], $override));
    }

    public function test_kirim_notifikasi_wa_saat_order_dikonfirmasi(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren);
        $order = $this->makeOrder($pesantren);

        app(UpgradeOrderService::class)->confirmOrder($order, $this->makeConfirmer());

        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => $job->phoneNumber === '081234567890'
            && str_contains($job->message, $pesantren->nama_pesantren)
        );
    }

    public function test_tidak_kirim_jika_admin_tanpa_phone_number(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren, null);
        $order = $this->makeOrder($pesantren);

        app(UpgradeOrderService::class)->confirmOrder($order, $this->makeConfirmer());

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_tidak_kirim_jika_toggle_dimatikan_super_admin(): void
    {
        Queue::fake();

        WhatsAppSetting::set('notif_order_dikonfirmasi_enabled', false);

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren);
        $order = $this->makeOrder($pesantren);

        app(UpgradeOrderService::class)->confirmOrder($order, $this->makeConfirmer());

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }

    public function test_pesan_menggunakan_template_kustom_dari_pengaturan(): void
    {
        Queue::fake();

        WhatsAppMessageTemplate::set(
            'notif_order_dikonfirmasi',
            'Halo {nama_pesantren}, paket {paket} aktif s.d. {tanggal_expired}, nomor {nomor_order}.',
        );

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren);
        $order = $this->makeOrder($pesantren);

        app(UpgradeOrderService::class)->confirmOrder($order, $this->makeConfirmer());

        $order->refresh();

        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => $job->message === "Halo {$pesantren->nama_pesantren}, paket Berkembang aktif s.d. {$order->expired_at_baru->format('d F Y')}, nomor {$order->nomor_order}."
        );
    }

    public function test_tidak_kirim_jika_pesantren_tidak_punya_admin(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren();
        $order = $this->makeOrder($pesantren);

        app(UpgradeOrderService::class)->confirmOrder($order, $this->makeConfirmer());

        Queue::assertNotPushed(KirimNotifikasiWhatsapp::class);
    }
}
