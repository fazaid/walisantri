@extends('mail.layout', [
    'judul' => 'Pembayaran Anda sudah kami terima',
    'aksiUrl' => 'https://'.config('app.domain').'/admin/billing-page',
    'aksiLabel' => 'Lihat Status Langganan',
    'penutup' => 'Terima kasih sudah mempercayakan pengelolaan data pesantren kepada kami.',
])

@section('isi')
    <p style="margin:0 0 12px;">Yth. Admin <strong>{{ $order->pesantren->nama_pesantren }}</strong>,</p>

    <p style="margin:0 0 12px;">
        Pembayaran untuk order <strong>{{ $order->nomor_order }}</strong> sudah kami verifikasi.
        Paket langganan Anda aktif mulai sekarang — tidak perlu keluar-masuk akun lagi.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
           style="margin:20px 0;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;">
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
            <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;color:#6b7280;">Total dibayar</td>
            <td style="padding:12px 16px;border-bottom:1px solid #f3f4f6;text-align:right;"><strong>{{ $order->formatted_harga }}</strong></td>
        </tr>
        <tr>
            <td style="padding:14px 16px;color:#111827;">Aktif sampai</td>
            <td style="padding:14px 16px;text-align:right;color:#0f766e;font-size:15px;"><strong>{{ $expiredAtBaru->locale('id')->translatedFormat('d F Y') }}</strong></td>
        </tr>
    </table>
@endsection
