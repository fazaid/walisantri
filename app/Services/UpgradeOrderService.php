<?php

namespace App\Services;

use App\Enums\DurasiLangganan;
use App\Enums\StatusOrder;
use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Kupon;
use App\Models\Order;
use App\Models\Pesantren;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpgradeOrderService
{
    public function __construct(
        private readonly BillingCalculatorService $calculator,
    ) {}

    public function createOrder(
        Pesantren $pesantren,
        string $paketTarget,
        int $durasibulan,
        int $maxSantriKuota,
        ?string $kodeKupon = null,
    ): array {
        $hasil = $this->calculator->hitungUntukTarget($paketTarget, $maxSantriKuota);
        $hargaPerBulan = $hasil['total_biaya'];
        $effectiveKuota = $hasil['kuota_maksimal'];

        $durasi = DurasiLangganan::from($durasibulan);
        $bonusBulan = $durasi->bonusBulan();
        $bulanBayar = $durasi->bulanBayar();   // bulan yang dibayar (misal 10 dari 12)
        $totalBulan = $durasi->totalBulan();   // total aktif = durasi yang dipilih (12)
        $hargaTotalSebelumDiskon = $hargaPerBulan * $bulanBayar;

        return DB::transaction(function () use (
            $pesantren, $paketTarget, $durasibulan, $maxSantriKuota,
            $hargaPerBulan, $effectiveKuota, $hargaTotalSebelumDiskon,
            $bonusBulan, $totalBulan, $kodeKupon
        ) {
            $kupon = null;
            $diskonNominal = 0;

            if ($kodeKupon) {
                $kupon = Kupon::where('kode', strtoupper($kodeKupon))->lockForUpdate()->first();

                if ($kupon && $kupon->isValid($durasibulan)) {
                    $diskonNominal = $kupon->hitungDiskon($hargaTotalSebelumDiskon);
                } else {
                    $kupon = null;
                }
            }

            $hargaTotal = max(0, $hargaTotalSebelumDiskon - $diskonNominal);

            $nomorOrder = $this->generateNomor(
                config('billing.nomor_order_prefix', 'WS'),
                'orders'
            );

            $order = Order::create([
                'pesantren_id' => $pesantren->id,
                'kupon_id' => $kupon?->id,
                'nomor_order' => $nomorOrder,
                'paket_target' => $paketTarget,
                'durasi_bulan' => $durasibulan,
                'max_santri_kuota_target' => $effectiveKuota,
                'harga_per_bulan' => $hargaPerBulan,
                'harga_total_sebelum_diskon' => $hargaTotalSebelumDiskon,
                'diskon_nominal' => $diskonNominal,
                'harga_total' => $hargaTotal,
                'bonus_bulan' => $bonusBulan,
                'durasi_total_bulan' => $totalBulan,
                'kode_kupon_snapshot' => $kodeKupon ? strtoupper($kodeKupon) : null,
                'status' => StatusOrder::PendingPayment,
            ]);

            $nomorInvoice = $this->generateNomor(
                config('billing.nomor_invoice_prefix', 'INV'),
                'invoices'
            );

            $invoice = Invoice::create([
                'order_id' => $order->id,
                'nomor_invoice' => $nomorInvoice,
            ]);

            if ($kupon) {
                $kupon->increment('jumlah_dipakai');
            }

            return compact('order', 'invoice');
        });
    }

    public function uploadBuktiTransfer(Invoice $invoice, UploadedFile $file): void
    {
        $order = $invoice->order;

        abort_unless($order->isPendingPayment(), 422, 'Status order tidak valid untuk upload bukti.');

        $path = $file->store("bukti-transfer/{$order->id}", 'local');

        $invoice->update([
            'bukti_transfer_path' => $path,
            'bukti_transfer_uploaded_at' => now(),
        ]);

        $order->update(['status' => StatusOrder::AwaitingConfirmation]);

        ActivityLog::create([
            'pesantren_id' => $order->pesantren_id,
            'user_id' => Auth::id(),
            'event' => 'order.bukti_uploaded',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'new_values' => ['nomor_order' => $order->nomor_order],
        ]);
    }

    public function confirmOrder(Order $order, User $confirmedBy, ?string $catatanAdmin = null): void
    {
        abort_unless(
            $order->isAwaitingConfirmation() || $order->isPendingPayment(),
            422,
            'Order tidak dalam status yang dapat dikonfirmasi.'
        );

        $hasil = DB::transaction(function () use ($order, $confirmedBy, $catatanAdmin) {
            $pesantren = $order->pesantren;

            $baseDate = ($pesantren->expired_at && $pesantren->expired_at->isFuture())
                ? $pesantren->expired_at
                : Carbon::now();

            $expiredAtBaru = $baseDate->copy()->addMonths($order->durasi_total_bulan);

            $pesantren->update([
                'paket_langganan' => $order->paket_target->value,
                'max_santri_kuota' => $order->max_santri_kuota_target,
                'status_berlangganan' => 'active',
                'expired_at' => $expiredAtBaru,
            ]);

            $order->update([
                'status' => StatusOrder::Confirmed,
                'confirmed_at' => now(),
                'confirmed_by' => $confirmedBy->id,
                'catatan_admin' => $catatanAdmin,
                'expired_at_baru' => $expiredAtBaru,
            ]);

            ActivityLog::create([
                'pesantren_id' => $pesantren->id,
                'user_id' => $confirmedBy->id,
                'event' => 'order.confirmed',
                'auditable_type' => Order::class,
                'auditable_id' => $order->id,
                'new_values' => [
                    'paket' => $order->paket_target->value,
                    'expired_at' => $expiredAtBaru->toDateTimeString(),
                ],
            ]);

            ActivityLog::create([
                'pesantren_id' => $pesantren->id,
                'user_id' => $confirmedBy->id,
                'event' => 'pesantren.paket_changed',
                'auditable_type' => Pesantren::class,
                'auditable_id' => $pesantren->id,
                'new_values' => ['paket' => $order->paket_target->value],
            ]);

            return compact('pesantren', 'expiredAtBaru');
        });

        $this->notifyOrderConfirmed($order, $hasil['pesantren'], $hasil['expiredAtBaru']);
    }

    // Pengecualian SEMPIT ketiga terhadap kebijakan "WA selalu manual" (PRD §12) —
    // notifikasi otomatis saat Super Admin mengonfirmasi order, di samping reminder
    // H-3/H-1 (WarnExpiringTenantsWhatsApp) dan notifikasi trial habis (CheckExpiredTenants).
    private function notifyOrderConfirmed(Order $order, Pesantren $pesantren, Carbon $expiredAtBaru): void
    {
        if (! WhatsAppSetting::get('notif_order_dikonfirmasi_enabled')) {
            return;
        }

        $pesantren->loadMissing('users');

        $admin = $pesantren->users
            ->where('role', 'admin_pesantren')
            ->first();

        if (! $admin || blank($admin->phone_number)) {
            return;
        }

        KirimNotifikasiWhatsapp::dispatch(
            $admin->phone_number,
            $this->buildOrderConfirmedMessage($order, $pesantren, $expiredAtBaru),
        );
    }

    private function buildOrderConfirmedMessage(Order $order, Pesantren $pesantren, Carbon $expiredAtBaru): string
    {
        $template = WhatsAppMessageTemplate::get('notif_order_dikonfirmasi', self::DEFAULT_ORDER_CONFIRMED_TEMPLATE);

        return strtr($template, [
            '{nama_pesantren}' => $pesantren->nama_pesantren,
            '{paket}' => $order->paket_target->label(),
            '{durasi_bulan}' => (string) $order->durasi_total_bulan,
            '{tanggal_expired}' => $expiredAtBaru->format('d F Y'),
            '{nomor_order}' => $order->nomor_order,
            '{total_dibayar}' => $order->formatted_harga,
            '{link_billing}' => url('/admin/billing-page'),
        ]);
    }

    private const DEFAULT_ORDER_CONFIRMED_TEMPLATE = <<<'TEXT'
    Assalamu'alaikum, Admin {nama_pesantren}.

    Pembayaran Anda telah dikonfirmasi Super Admin.

    Nomor order   : {nomor_order}
    Paket aktif   : {paket}
    Durasi        : {durasi_bulan} bulan
    Total dibayar : {total_dibayar}
    Aktif hingga  : {tanggal_expired}

    Terima kasih telah berlangganan Walisantri.com.
    {link_billing}
    TEXT;

    public function rejectOrder(Order $order, User $rejectedBy, string $catatanAdmin): void
    {
        abort_unless($order->isAwaitingConfirmation(), 422, 'Order tidak dalam status menunggu konfirmasi.');

        $order->update([
            'status' => StatusOrder::Rejected,
            'catatan_admin' => $catatanAdmin,
            'confirmed_at' => now(),
            'confirmed_by' => $rejectedBy->id,
        ]);

        ActivityLog::create([
            'pesantren_id' => $order->pesantren_id,
            'user_id' => $rejectedBy->id,
            'event' => 'order.rejected',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'new_values' => ['catatan' => $catatanAdmin],
        ]);
    }

    private function generateNomor(string $prefix, string $table): string
    {
        $tanggal = now()->format('Ymd');
        $count = DB::table($table)
            ->whereDate('created_at', today())
            ->count();

        return $prefix.'-'.$tanggal.'-'.str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
