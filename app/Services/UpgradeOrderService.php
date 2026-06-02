<?php

namespace App\Services;

use App\Enums\DurasiLangganan;
use App\Enums\StatusOrder;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Kupon;
use App\Models\Order;
use App\Models\Pesantren;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        $durasi      = DurasiLangganan::from($durasibulan);
        $bonusBulan  = $durasi->bonusBulan();
        $bulanBayar  = $durasi->bulanBayar();   // bulan yang dibayar (misal 10 dari 12)
        $totalBulan  = $durasi->totalBulan();   // total aktif = durasi yang dipilih (12)
        $hargaTotalSebelumDiskon = $hargaPerBulan * $bulanBayar;

        $diskonNominal = 0;
        $kupon         = null;

        if ($kodeKupon) {
            $kupon = Kupon::where('kode', strtoupper($kodeKupon))->first();
            if ($kupon && $kupon->isValid($durasibulan)) {
                $diskonNominal = $kupon->hitungDiskon($hargaTotalSebelumDiskon);
            }
        }

        $hargaTotal = max(0, $hargaTotalSebelumDiskon - $diskonNominal);

        return DB::transaction(function () use (
            $pesantren, $paketTarget, $durasibulan, $maxSantriKuota,
            $hargaPerBulan, $hargaTotalSebelumDiskon, $diskonNominal,
            $hargaTotal, $bonusBulan, $totalBulan, $kodeKupon, $kupon
        ) {
            $nomorOrder = $this->generateNomor(
                config('billing.nomor_order_prefix', 'WS'),
                'orders'
            );

            $order = Order::create([
                'pesantren_id'               => $pesantren->id,
                'kupon_id'                   => $kupon?->id,
                'nomor_order'                => $nomorOrder,
                'paket_target'               => $paketTarget,
                'durasi_bulan'               => $durasibulan,
                'max_santri_kuota_target'    => $maxSantriKuota,
                'harga_per_bulan'            => $hargaPerBulan,
                'harga_total_sebelum_diskon' => $hargaTotalSebelumDiskon,
                'diskon_nominal'             => $diskonNominal,
                'harga_total'                => $hargaTotal,
                'bonus_bulan'                => $bonusBulan,
                'durasi_total_bulan'         => $totalBulan,
                'kode_kupon_snapshot'        => $kodeKupon ? strtoupper($kodeKupon) : null,
                'status'                     => StatusOrder::PendingPayment,
            ]);

            $nomorInvoice = $this->generateNomor(
                config('billing.nomor_invoice_prefix', 'INV'),
                'invoices'
            );

            $invoice = Invoice::create([
                'order_id'       => $order->id,
                'nomor_invoice'  => $nomorInvoice,
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
            'bukti_transfer_path'         => $path,
            'bukti_transfer_uploaded_at'  => now(),
        ]);

        $order->update(['status' => StatusOrder::AwaitingConfirmation]);

        ActivityLog::create([
            'pesantren_id'   => $order->pesantren_id,
            'user_id'        => Auth::id(),
            'event'          => 'order.bukti_uploaded',
            'auditable_type' => Order::class,
            'auditable_id'   => $order->id,
            'new_values'     => ['nomor_order' => $order->nomor_order],
        ]);
    }

    public function confirmOrder(Order $order, User $confirmedBy, ?string $catatanAdmin = null): void
    {
        abort_unless($order->isAwaitingConfirmation(), 422, 'Order tidak dalam status menunggu konfirmasi.');

        DB::transaction(function () use ($order, $confirmedBy, $catatanAdmin) {
            $pesantren = $order->pesantren;

            $baseDate = ($pesantren->expired_at && $pesantren->expired_at->isFuture())
                ? $pesantren->expired_at
                : Carbon::now();

            $expiredAtBaru = $baseDate->copy()->addMonths($order->durasi_total_bulan);

            $pesantren->update([
                'paket_langganan'     => $order->paket_target->value,
                'max_santri_kuota'    => $order->max_santri_kuota_target,
                'status_berlangganan' => 'active',
                'expired_at'          => $expiredAtBaru,
            ]);

            $order->update([
                'status'          => StatusOrder::Confirmed,
                'confirmed_at'    => now(),
                'confirmed_by'    => $confirmedBy->id,
                'catatan_admin'   => $catatanAdmin,
                'expired_at_baru' => $expiredAtBaru,
            ]);

            ActivityLog::create([
                'pesantren_id'   => $pesantren->id,
                'user_id'        => $confirmedBy->id,
                'event'          => 'order.confirmed',
                'auditable_type' => Order::class,
                'auditable_id'   => $order->id,
                'new_values'     => [
                    'paket'      => $order->paket_target->value,
                    'expired_at' => $expiredAtBaru->toDateTimeString(),
                ],
            ]);

            ActivityLog::create([
                'pesantren_id'   => $pesantren->id,
                'user_id'        => $confirmedBy->id,
                'event'          => 'pesantren.paket_changed',
                'auditable_type' => Pesantren::class,
                'auditable_id'   => $pesantren->id,
                'new_values'     => ['paket' => $order->paket_target->value],
            ]);
        });
    }

    public function rejectOrder(Order $order, User $rejectedBy, string $catatanAdmin): void
    {
        abort_unless($order->isAwaitingConfirmation(), 422, 'Order tidak dalam status menunggu konfirmasi.');

        $order->update([
            'status'        => StatusOrder::Rejected,
            'catatan_admin' => $catatanAdmin,
            'confirmed_at'  => now(),
            'confirmed_by'  => $rejectedBy->id,
        ]);

        ActivityLog::create([
            'pesantren_id'   => $order->pesantren_id,
            'user_id'        => $rejectedBy->id,
            'event'          => 'order.rejected',
            'auditable_type' => Order::class,
            'auditable_id'   => $order->id,
            'new_values'     => ['catatan' => $catatanAdmin],
        ]);
    }

    private function generateNomor(string $prefix, string $table): string
    {
        $tanggal = now()->format('Ymd');
        $count   = DB::table($table)
            ->whereDate('created_at', today())
            ->lockForUpdate()
            ->count();

        return $prefix . '-' . $tanggal . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
