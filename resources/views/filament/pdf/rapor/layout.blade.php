{{-- Kerangka bersama PDF rapor: CSS + kop pesantren + identitas santri dipakai
     ulang seluruh modul, menggantikan empat salinan CSS di template PDF lama. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { margin: 2.2cm 1.8cm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #166534;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header .logo { height: 44px; margin-bottom: 6px; }
        .header .pesantren-name { font-size: 18px; font-weight: bold; color: #166534; }
        .header .meta { font-size: 10px; color: #555; margin-top: 4px; }

        .info-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }
        .info-card table { width: 100%; }
        .info-card td { padding: 2px 4px; font-size: 10px; color: #374151; }
        .info-card td:first-child { color: #6b7280; width: 110px; }

        .modul { page-break-before: always; }
        .modul-pertama { page-break-before: avoid; }

        .modul-title {
            font-size: 13px;
            font-weight: bold;
            color: #166534;
            border-bottom: 1px solid #bbf7d0;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }

        .section-title {
            background: #f0fdf4;
            border-left: 3px solid #16a34a;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: bold;
            color: #166534;
            margin: 14px 0 6px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
        }
        /*
         * Baris judul diulang di tiap halaman saat tabel pecah.
         *
         * Wajib dipasangkan dengan <thead> di templatenya — tanpa itu, tabel santri
         * ber-mapel banyak menyambung ke halaman berikutnya tanpa baris judul, dan
         * kolom angkanya kehilangan arti sepenuhnya. Tidak ada satu pun <thead> di
         * modul rapor sebelum ini.
         */
        table.data-table thead { display: table-header-group; }
        table.data-table tr { page-break-inside: avoid; }

        table.data-table th {
            background: #166534;
            color: #fff;
            padding: 5px 8px;
            text-align: left;
        }
        table.data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        table.data-table tr:last-child td { border-bottom: none; }
        table.data-table tr:nth-child(even) td { background: #f9fafb; }
        .text-center { text-align: center; }

        .badge {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
        .badge-hijau  { background: #dcfce7; color: #166534; }
        .badge-biru   { background: #dbeafe; color: #1d4ed8; }
        .badge-kuning { background: #fef9c3; color: #854d0e; }
        .badge-merah  { background: #fee2e2; color: #991b1b; }
        .badge-oranye { background: #ffedd5; color: #9a3412; }

        /* Nilai huruf rapor karakter: kotak, bukan pil seperti .badge biasa. */
        .badge-huruf {
            display: inline-block;
            width: 22px;
            height: 22px;
            line-height: 22px;
            text-align: center;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
        }
        /* Varian legenda — sebelumnya ditulis inline sebagai style bertiga
           (font-size + width/height + line-height) di karakter.blade.php. */
        .badge-huruf-kecil { width: 16px; height: 16px; line-height: 16px; font-size: 9px; }

        .badge-a { background: #dcfce7; color: #166534; }
        .badge-b { background: #dbeafe; color: #1d4ed8; }
        .badge-c { background: #fef9c3; color: #854d0e; }
        .badge-d { background: #fee2e2; color: #991b1b; }

        .keterangan-wrap { margin-top: 8px; }
        .keterangan-item { display: inline-block; margin-right: 16px; font-size: 9px; color: #374151; }

        .catatan-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 10px;
            color: #374151;
            white-space: pre-line;
            margin-top: 6px;
        }

        /* Kartu statistik tahfidz: jumlah kolom mengikuti tipe setoran. */
        .stat-grid { width: 100%; margin-bottom: 10px; }
        .stat-grid td {
            text-align: center;
            padding: 8px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        .stat-grid .stat-label { display: block; font-size: 9px; color: #6b7280; margin-bottom: 2px; }
        .stat-grid .stat-value { display: block; font-size: 14px; font-weight: bold; color: #1a1a1a; }

        /* Kartu statistik mutabaah: selalu empat kolom sama lebar. */
        .stat-grid-4 { width: 100%; margin-bottom: 14px; }
        .stat-grid-4 td {
            width: 25%;
            text-align: center;
            border: 1px solid #e5e7eb;
            padding: 8px;
            border-radius: 4px;
        }
        .stat-grid-4 .stat-num { font-size: 20px; font-weight: bold; color: #166534; }
        .stat-grid-4 .stat-label { font-size: 9px; color: #6b7280; margin-top: 2px; }

        .penguji { color: #6b7280; font-size: 9px; margin-top: 1px; }

        .surah-tag {
            display: inline-block;
            padding: 2px 8px;
            margin: 2px;
            border-radius: 10px;
            background: #f0fdf4;
            color: #166534;
            font-size: 9px;
        }

        .distribusi-row { display: table; width: 100%; margin-bottom: 3px; }
        .distribusi-row .d-label { display: table-cell; width: 110px; font-size: 10px; color: #374151; }
        .distribusi-row .d-value {
            display: table-cell;
            width: 70px;
            font-size: 10px;
            color: #1a1a1a;
            font-weight: bold;
            text-align: right;
        }

        .progress-bar-wrap {
            background: #e5e7eb;
            border-radius: 3px;
            height: 8px;
            width: 80px;
            display: inline-block;
            vertical-align: middle;
        }
        .progress-bar-fill { height: 8px; border-radius: 3px; }
        .bar-hijau  { background: #16a34a; }
        .bar-kuning { background: #ca8a04; }
        .bar-merah  { background: #dc2626; }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8.5px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }
        .footer table { width: 100%; }
        .footer td { font-size: 8.5px; }
        .footer .identitas { text-align: left; }
        .footer .nomor { text-align: right; white-space: nowrap; }
        .footer .nomor:after { content: "Halaman " counter(page) " dari " counter(pages); }
        .footer .jejak { text-align: center; color: #9ca3af; font-size: 8px; margin-top: 1px; }

        .ttd-blok {
            page-break-inside: avoid;
            margin-top: 24px;
            font-size: 10px;
        }
        .ttd-blok .ttd-tempat { text-align: right; margin-bottom: 4px; color: #374151; }
        .ttd-blok table { width: 100%; }
        .ttd-blok td { width: 50%; text-align: center; vertical-align: top; }
        .ttd-blok .jabatan { color: #6b7280; }
        /* Ruang tanda tangan basah — tinggi tetap, bukan <br> berulang. */
        .ttd-blok .ruang-tanda-tangan { height: 52px; }
        .ttd-blok .nama { font-weight: bold; border-top: 1px solid #9ca3af; padding-top: 3px; display: inline-block; min-width: 60%; }

        .no-data { color: #9ca3af; font-style: italic; font-size: 10px; }

        /*
         * Catatan penjelasan di bawah tabel — BUKAN .no-data.
         *
         * Sebelumnya modul presensi memakai `class="no-data" style="font-style:normal"`
         * untuk ini, sehingga keterangan yang justru paling perlu dibaca (bahwa
         * "Tanpa Keterangan" bukan bukti ketidakhadiran) tampil abu-abu pucat dan
         * terbaca seperti placeholder "belum ada data".
         */
        .catatan-kaki {
            font-size: 9.5px;
            color: #4b5563;
            background: #f9fafb;
            border-left: 2px solid #d1d5db;
            padding: 5px 8px;
            margin: 6px 0 4px;
            line-height: 1.45;
        }

        /* Angka kunci satu baris — dipakai akademik (rata-rata) & tahfidz (juz). */
        .angka-kunci {
            display: inline-block;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            padding: 4px 10px;
            font-size: 10px;
            color: #374151;
            margin-bottom: 8px;
        }
        .angka-kunci strong { font-size: 13px; color: #166534; }

        .catatan-inline { font-size: 10px; margin-bottom: 8px; }
        .sub-judul { font-size: 10px; font-weight: bold; color: #374151; margin-bottom: 4px; }
        .jarak-bawah { margin-bottom: 10px; }
    </style>
</head>
<body>

{{--
    Footer ini SATU-SATUNYA hal yang terulang di tiap halaman, jadi ia yang memikul
    identitas dokumen. Kop dan kartu identitas hanya tercetak di halaman pertama —
    sementara tiap modul memulai halaman baru, sehingga rapor lima modul jadi lima
    halaman. Tanpa baris ini, halaman 2 dan seterusnya tidak menyebut nama santri
    sama sekali: satu lembar yang lepas dari staples tidak bisa dikembalikan ke
    pemiliknya, dan tidak ada yang tahu kalau ada halaman yang hilang.
--}}
<div class="footer">
    <table>
        <tr>
            <td class="identitas">
                {{ $santri->nama_lengkap }}
                @if($santri->kelas?->nama_kelas) &middot; {{ $santri->kelas->nama_kelas }} @endif
                &middot; {{ $tahunAjaran }} {{ $periodeLabel }}
            </td>
            {{-- counter(page)/counter(pages) didukung DomPDF di konfigurasi ini
                 (enable_php mati, jadi $PAGE_NUM bukan pilihan). --}}
            <td class="nomor"></td>
        </tr>
    </table>
    <div class="jejak">
        Dicetak via Walisantri.com — {{ now()->timezone(config('app.display_timezone'))->translatedFormat('d M Y, H:i') }} WIB
    </div>
</div>

<div class="header">
    @if($santri->pesantren?->logo_path)
    <img src="{{ $santri->pesantren->logo_path }}" class="logo" alt="Logo">
    @endif
    @if($santri->pesantren)
    <div class="pesantren-name">{{ $santri->pesantren->nama_pesantren }}</div>
    @endif
    <div class="meta">RAPOR SANTRI</div>
</div>

<div class="info-card">
    <table>
        <tr>
            <td>Nama Santri</td>
            <td>: <strong>{{ $santri->nama_lengkap }}</strong></td>
            <td>Tahun Ajaran</td>
            <td>: {{ $tahunAjaran }}</td>
        </tr>
        <tr>
            <td>NIS</td>
            <td>: {{ $santri->nis ?? '—' }}</td>
            <td>Periode</td>
            <td>: {{ $periodeLabel }}</td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>: {{ $santri->kelas?->nama_kelas ?? '—' }}</td>
            <td>Isi Rapor</td>
            <td>: {{ $isiRapor }}</td>
        </tr>
    </table>
</div>

@yield('isi')

{{--
    Blok tanda tangan — penutup wajib sebuah rapor, dan sebelumnya tidak ada sama
    sekali. page-break-inside: avoid supaya ia tidak pernah terbelah dua halaman;
    kalau ruang di halaman terakhir tidak cukup, seluruh bloknya pindah utuh.
--}}
@php
    // Kota diambil dari wilayah profil pesantren (§4.1). Tenant lama yang belum
    // punya wilayah cukup mencetak tanggalnya saja — baris "Kota, tanggal" yang
    // kotanya kosong justru terbaca seperti data yang gagal dimuat.
    $kotaPesantren = $santri->pesantren?->profil['wilayah']['kota']['nama'] ?? null;
@endphp
<div class="ttd-blok">
    <div class="ttd-tempat">
        {{ $kotaPesantren ? $kotaPesantren.', ' : '' }}{{ now()->timezone(config('app.display_timezone'))->locale('id')->translatedFormat('d F Y') }}
    </div>
    <table>
        <tr>
            <td>
                <div class="jabatan">Wali Kelas</div>
                <div class="ruang-tanda-tangan"></div>
                <div class="nama">{{ $santri->kelas?->waliKelas?->name ?? '(_______________________)' }}</div>
            </td>
            <td>
                <div class="jabatan">Kepala Pesantren</div>
                <div class="ruang-tanda-tangan"></div>
                {{-- Diisi di Pengaturan → Identitas Pesantren. Kalau kosong, garis
                     titik-titik tetap dicetak supaya bisa ditandatangani manual. --}}
                <div class="nama">{{ $santri->pesantren?->kepala_pesantren ?? '(_______________________)' }}</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
