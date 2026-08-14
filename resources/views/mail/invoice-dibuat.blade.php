@extends('mail.layout', [
    'judul' => 'Invoice '.$invoice->nomor_invoice,
    'aksiUrl' => 'https://'.config('app.domain').'/admin/order-invoice-page?order='.$order->id,
    'aksiLabel' => 'Lihat Invoice & Unggah Bukti',
    'penutup' => 'Setelah transfer, unggah bukti pembayaran lewat tombol di atas. Tim kami memverifikasi dalam 1×24 jam.',
])

@section('isi')
    <p style="margin:0 0 12px;">Yth. Admin <strong>{{ $order->pesantren->nama_pesantren }}</strong>,</p>

    <p style="margin:0 0 12px;">
        Pesanan Anda sudah kami catat dan menunggu pembayaran. Rinciannya juga terlampir
        sebagai berkas PDF pada email ini.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
           style="margin:20px 0;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;">
        <tr>
            <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;color:#6b7280;">Nomor order</td>
            <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;text-align:right;"><strong>{{ $order->nomor_order }}</strong></td>
        </tr>
        <tr>
            <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;color:#6b7280;">Paket</td>
            <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;text-align:right;"><strong>{{ $order->paket_target->label() }}</strong></td>
        </tr>
        <tr>
            <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;color:#6b7280;">Durasi</td>
            <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;text-align:right;"><strong>{{ $order->durasi_total_bulan }} bulan</strong></td>
        </tr>
        <tr>
            <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;color:#6b7280;">Kuota santri</td>
            <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;text-align:right;"><strong>{{ number_format($order->max_santri_kuota_target, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td style="padding:14px 16px;color:#111827;font-size:15px;"><strong>Total tagihan</strong></td>
            <td style="padding:14px 16px;text-align:right;color:#0f766e;font-size:16px;"><strong>{{ $order->formatted_harga }}</strong></td>
        </tr>
    </table>
@endsection
