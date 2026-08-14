@extends('mail.layout', ['judul' => 'Koneksi email berhasil'])

@section('isi')
    <p style="margin:0 0 12px;">
        Kalau Anda membaca pesan ini, berarti kredensial SMTP yang tersimpan sudah benar
        dan platform bisa mengirim email ke dunia luar.
    </p>
    <p style="margin:0;">
        Email ini dikirim dari halaman <strong>Pengaturan Email</strong> dan tidak dikirim
        ke siapa pun selain Anda.
    </p>
@endsection
