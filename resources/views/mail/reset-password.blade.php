@extends('mail.layout', [
    'judul' => 'Atur ulang kata sandi',
    'aksiUrl' => $url,
    'aksiLabel' => 'Atur Ulang Kata Sandi',
    'penutup' => 'Jika Anda tidak meminta ini, abaikan saja email ini — kata sandi Anda tidak berubah selama tautan di atas tidak dibuka.',
])

@section('isi')
    <p style="margin:0 0 12px;">Yth. <strong>{{ $nama }}</strong>,</p>

    <p style="margin:0 0 12px;">
        Kami menerima permintaan untuk mengatur ulang kata sandi akun <strong>{{ $email }}</strong>.
        Tekan tombol di bawah untuk memilih kata sandi baru.
    </p>

    <p style="margin:0;">
        Tautan ini hanya berlaku <strong>{{ $menitBerlaku }} menit</strong> dan hanya bisa dipakai sekali.
    </p>
@endsection
