@extends('mail.layout', [
    'judul' => 'Langganan akan berakhir dalam '.$daysLeft.' hari',
    'aksiUrl' => 'https://'.config('app.domain').'/admin/billing-page',
    'aksiLabel' => 'Perpanjang Sekarang',
    'penutup' => 'Sudah memperpanjang? Abaikan email ini — status langganan diperbarui setelah pembayaran diverifikasi.',
])

@section('isi')
    <p style="margin:0 0 12px;">Yth. Admin <strong>{{ $pesantren->nama_pesantren }}</strong>,</p>

    <p style="margin:0 0 12px;">
        Langganan Anda di Walisantri.com akan berakhir dalam <strong>{{ $daysLeft }} hari</strong>,
        tepatnya pada <strong>{{ $pesantren->expired_at->locale('id')->translatedFormat('d F Y') }}</strong>.
    </p>

    <p style="margin:0;">
        Setelah masa aktif habis, panel admin terkunci ke halaman langganan dan portal wali
        beralih ke mode baca-saja. Data santri Anda tetap aman dan utuh — akses kembali
        normal begitu perpanjangan dikonfirmasi.
    </p>
@endsection
