@extends('mail.layout', [
    'judul' => 'Konfirmasi alamat email Anda',
    'aksiUrl' => $url,
    'aksiLabel' => 'Konfirmasi Alamat Email',
    'penutup' => 'Tidak merasa meminta ini? Abaikan saja — tidak ada yang berubah pada akun Anda.',
])

@section('isi')
    <p style="margin:0 0 12px;">Yth. <strong>{{ $nama }}</strong>,</p>

    <p style="margin:0 0 12px;">
        Satu klik untuk memastikan alamat email ini benar. Konfirmasi penting karena
        tagihan, bukti pembayaran, dan peringatan masa aktif langganan semuanya kami
        kirim ke sini — kalau alamatnya keliru, pemberitahuan itu tidak akan pernah sampai.
    </p>

    <p style="margin:0;">
        Tautan ini berlaku <strong>{{ $menitBerlaku }} menit</strong>. Akun Anda tetap bisa
        dipakai seperti biasa meski belum dikonfirmasi.
    </p>
@endsection
