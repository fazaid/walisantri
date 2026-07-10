<?php

namespace Tests\Feature\Services;

use App\Enums\StatusOrder;
use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Kupon;
use App\Models\Order;
use App\Models\Pesantren;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use App\Services\UpgradeOrderService;
use Carbon\Carbon;
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

    /**
     * Regresi: addMonths() biasa meluber ke bulan berikutnya kalau anchor-nya
     * tanggal 29-31 dan bulan target lebih pendek (mis. 31 Jan + 1 bulan = 3
     * Maret, bukan 28 Feb). Karena expired_at baru jadi anchor renewal
     * berikutnya juga, drift ini bisa terus terbawa di setiap perpanjangan.
     */
    public function test_perpanjangan_dari_akhir_bulan_tidak_meluber_ke_bulan_berikutnya(): void
    {
        Queue::fake();

        $pesantren = $this->makePesantren([
            'expired_at' => Carbon::parse('2027-01-31'),
        ]);
        $this->makeAdmin($pesantren);
        $order = $this->makeOrder($pesantren, ['durasi_total_bulan' => 1]);

        app(UpgradeOrderService::class)->confirmOrder($order, $this->makeConfirmer());

        $this->assertSame('2027-02-28', $pesantren->fresh()->expired_at->format('Y-m-d'));
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

        Queue::assertPushed(KirimNotifikasiWhatsapp::class, fn ($job) => $job->message === "Halo {$pesantren->nama_pesantren}, paket Berkembang aktif s.d. {$order->expired_at_baru->locale('id')->translatedFormat('d F Y')}, nomor {$order->nomor_order}."
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

    private function makeKupon(array $override = []): Kupon
    {
        return Kupon::create(array_merge([
            'kode' => 'TESTKUPON',
            'tipe_diskon' => 'nominal',
            'nilai_diskon' => 10000,
            'min_durasi_bulan' => null,
            'max_penggunaan' => null,
            'jumlah_dipakai' => 0,
            'berlaku_hingga' => null,
            'is_aktif' => true,
        ], $override));
    }

    public function test_kupon_valid_menambah_jumlah_dipakai_dan_diskon(): void
    {
        $pesantren = $this->makePesantren();
        $kupon = $this->makeKupon();

        $result = app(UpgradeOrderService::class)->createOrder(
            pesantren: $pesantren,
            paketTarget: 'rintisan',
            durasibulan: 1,
            maxSantriKuota: 100,
            kodeKupon: 'testkupon',
        );

        $kupon->refresh();

        $this->assertSame(1, $kupon->jumlah_dipakai);
        $this->assertSame($kupon->id, $result['order']->kupon_id);
        $this->assertSame(10000, $result['order']->diskon_nominal);
    }

    public function test_kupon_nonaktif_tidak_menambah_jumlah_dipakai(): void
    {
        $pesantren = $this->makePesantren();
        $kupon = $this->makeKupon(['is_aktif' => false]);

        $result = app(UpgradeOrderService::class)->createOrder(
            pesantren: $pesantren,
            paketTarget: 'rintisan',
            durasibulan: 1,
            maxSantriKuota: 100,
            kodeKupon: 'testkupon',
        );

        $kupon->refresh();

        $this->assertSame(0, $kupon->jumlah_dipakai);
        $this->assertNull($result['order']->kupon_id);
        $this->assertSame(0, $result['order']->diskon_nominal);
    }

    public function test_kupon_kadaluwarsa_tidak_menambah_jumlah_dipakai(): void
    {
        $pesantren = $this->makePesantren();
        $kupon = $this->makeKupon(['berlaku_hingga' => now()->subDay()]);

        app(UpgradeOrderService::class)->createOrder(
            pesantren: $pesantren,
            paketTarget: 'rintisan',
            durasibulan: 1,
            maxSantriKuota: 100,
            kodeKupon: 'testkupon',
        );

        $kupon->refresh();

        $this->assertSame(0, $kupon->jumlah_dipakai);
    }

    public function test_kupon_yang_sudah_mencapai_max_penggunaan_tidak_bertambah_lagi(): void
    {
        $pesantren = $this->makePesantren();
        $kupon = $this->makeKupon(['max_penggunaan' => 1, 'jumlah_dipakai' => 1]);

        app(UpgradeOrderService::class)->createOrder(
            pesantren: $pesantren,
            paketTarget: 'rintisan',
            durasibulan: 1,
            maxSantriKuota: 100,
            kodeKupon: 'testkupon',
        );

        $kupon->refresh();

        $this->assertSame(1, $kupon->jumlah_dipakai);
    }

    public function test_kode_kupon_tidak_ditemukan_tidak_error(): void
    {
        $pesantren = $this->makePesantren();

        $result = app(UpgradeOrderService::class)->createOrder(
            pesantren: $pesantren,
            paketTarget: 'rintisan',
            durasibulan: 1,
            maxSantriKuota: 100,
            kodeKupon: 'TIDAKADA',
        );

        $this->assertNull($result['order']->kupon_id);
        $this->assertSame(0, $result['order']->diskon_nominal);
    }
}
