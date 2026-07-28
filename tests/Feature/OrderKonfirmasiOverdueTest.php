<?php

namespace Tests\Feature;

use App\Enums\StatusOrder;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Pesantren;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon as SupportCarbon;
use Tests\TestCase;

class OrderKonfirmasiOverdueTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    protected function setUp(): void
    {
        parent::setUp();

        // Bekukan waktu ke hari kerja (Rabu) supaya slaCutoff() deterministik.
        $this->travelTo(SupportCarbon::parse('2026-07-22 10:00:00'));

        $this->pesantren = Pesantren::create([
            'nama_pesantren' => 'Pesantren Badge Test',
            'slug' => 'pesantren-badge-'.uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addMonth(),
        ]);
    }

    private function makeOrder(StatusOrder $status, ?Carbon $uploadedAt = null): Order
    {
        $order = Order::create([
            'pesantren_id' => $this->pesantren->id,
            'nomor_order' => 'WS-'.uniqid(),
            'paket_target' => 'berkembang',
            'durasi_bulan' => 12,
            'max_santri_kuota_target' => 500,
            'harga_per_bulan' => 100000,
            'harga_total_sebelum_diskon' => 1200000,
            'harga_total' => 1200000,
            'durasi_total_bulan' => 12,
            'status' => $status,
        ]);

        Invoice::create([
            'order_id' => $order->id,
            'nomor_invoice' => 'INV-'.uniqid(),
            'bukti_transfer_path' => $uploadedAt ? 'bukti-transfer/dummy.jpg' : null,
            'bukti_transfer_uploaded_at' => $uploadedAt,
        ]);

        return $order->refresh();
    }

    public function test_badge_hanya_menghitung_order_menunggu_konfirmasi(): void
    {
        $this->makeOrder(StatusOrder::AwaitingConfirmation, now());
        $this->makeOrder(StatusOrder::AwaitingConfirmation, now());
        $this->makeOrder(StatusOrder::PendingPayment);
        $this->makeOrder(StatusOrder::Confirmed, now()->subWeek());
        $this->makeOrder(StatusOrder::Rejected, now()->subWeek());

        $this->assertSame('2', OrderResource::getNavigationBadge());
    }

    public function test_badge_disembunyikan_saat_antrean_kosong(): void
    {
        $this->makeOrder(StatusOrder::PendingPayment);

        $this->assertNull(OrderResource::getNavigationBadge());
    }

    public function test_warna_badge_merah_hanya_saat_ada_yang_melewati_sla(): void
    {
        $onTime = $this->makeOrder(StatusOrder::AwaitingConfirmation, Order::slaCutoff());

        $this->assertSame('warning', OrderResource::getNavigationBadgeColor());

        $onTime->invoice->update([
            'bukti_transfer_uploaded_at' => Order::slaCutoff()->subSecond(),
        ]);

        $this->assertSame('danger', OrderResource::getNavigationBadgeColor());
    }

    public function test_scope_dan_isoverdue_selalu_konsisten(): void
    {
        $cutoff = Order::slaCutoff();

        $overdue = $this->makeOrder(StatusOrder::AwaitingConfirmation, $cutoff->copy()->subSecond());
        $onBoundary = $this->makeOrder(StatusOrder::AwaitingConfirmation, $cutoff->copy());
        $fresh = $this->makeOrder(StatusOrder::AwaitingConfirmation, $cutoff->copy()->addSecond());
        // Bukti sudah lama masuk, tapi sudah dikonfirmasi → tidak boleh overdue.
        $confirmed = $this->makeOrder(StatusOrder::Confirmed, $cutoff->copy()->subWeek());
        // Belum bayar → bola masih di pesantren, bukan antrean superadmin.
        $belumBayar = $this->makeOrder(StatusOrder::PendingPayment);

        $overdueIds = Order::query()->overdue()->pluck('id');

        foreach ([$overdue, $onBoundary, $fresh, $confirmed, $belumBayar] as $order) {
            $this->assertSame(
                $order->isOverdue(),
                $overdueIds->contains($order->id),
                "isOverdue() dan scopeOverdue() harus sepakat untuk order #{$order->id}",
            );
        }

        $this->assertEquals([$overdue->id], $overdueIds->all());
    }
}
