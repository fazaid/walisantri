@extends('mail.layout', [
    'judul' => 'Selamat datang, '.$pesantren->nama_pesantren,
    'aksiUrl' => $urlVerifikasi,
    'aksiLabel' => 'Konfirmasi Alamat Email',
    'penutup' => 'Akun Anda sudah bisa dipakai sekarang juga — konfirmasi di atas hanya memastikan tagihan dan peringatan masa aktif benar-benar sampai ke alamat ini.',
])

@section('isi')
    <p style="margin:0 0 12px;">Yth. <strong>{{ $admin->name }}</strong>,</p>

    <p style="margin:0 0 12px;">
        Akun pesantren Anda sudah aktif. Mulai sekarang data santri, hafalan, mutaba'ah,
        dan keuangan bisa dikelola di satu tempat — dan wali santri bisa memantau
        perkembangan anaknya sendiri.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
           style="margin:20px 0;border:1px solid #e5e7eb;border-radius:8px;">
        <tr>
            <td style="padding:14px 16px;border-bottom:1px solid #f3f4f6;font-size:14px;">
                <span style="color:#6b7280;">Paket</span><br>
                <strong>Trial {{ ucfirst($pesantren->paket_langganan) }}</strong> — kuota {{ number_format($pesantren->max_santri_kuota, 0, ',', '.') }} santri
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px;border-bottom:1px solid #f3f4f6;font-size:14px;">
                <span style="color:#6b7280;">Masa percobaan berakhir</span><br>
                <strong>{{ $pesantren->expired_at?->locale('id')->translatedFormat('d F Y') ?? '-' }}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px;font-size:14px;">
                <span style="color:#6b7280;">Alamat situs profil pesantren</span><br>
                <strong>{{ $pesantren->slug }}.{{ config('app.base_domain') }}</strong>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;">
        Panel admin ada di
        <a href="https://{{ config('app.domain') }}/admin" style="color:#0f766e;">{{ config('app.domain') }}/admin</a>.
    </p>

    <p style="margin:16px 0 8px;"><strong>Tiga langkah pertama yang kami sarankan:</strong></p>
    <ol style="margin:0;padding-left:20px;line-height:1.8;">
        <li>Lengkapi profil pesantren agar situs publiknya siap dilihat calon wali.</li>
        <li>Tambahkan ustadz dan kelas, lalu masukkan data santri (bisa impor massal dari Excel).</li>
        <li>Bagikan Link Wali ke orang tua santri — mereka tidak perlu membuat akun.</li>
    </ol>
@endsection
