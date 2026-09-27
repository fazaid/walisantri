<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            margin: 2.2cm 1.8cm;
        }

        /*
         * DomPDF menerapkan margin @page lewat kotak margin <body> — bukan lapisan
         * terpisah seperti di browser. Reset universal di atas ("* { margin: 0 }")
         * ikut kena ke <body> dan MENIMPA margin itu, sehingga @page di atas
         * efektif diabaikan: kontennya mulai dari dekat tepi kertas, bukan
         * 2.2cm/1.8cm — sama persis dengan bug yang diperbaiki di PDF rapor panel
         * admin (lihat resources/views/filament/pdf/rapor/layout.blade.php).
         * Menuliskan ulang margin yang sama persis di sini — selectornya lebih
         * spesifik dari "*" jadi menang di cascade — mengembalikan keduanya.
         */
        body {
            margin: 2.2cm 1.8cm;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        /* ── Header ─────────────────────────────────── */
        .header {
            text-align: center;
            border-bottom: 2px solid #166534;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header .logo {
            height: 44px;
            margin-bottom: 6px;
        }
        .header .pesantren-name {
            font-size: 18px;
            font-weight: bold;
            color: #166534;
        }
        .header .meta {
            font-size: 10px;
            color: #555;
            margin-top: 4px;
        }

        /* ── Santri Info Card ───────────────────────── */
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

        /* ── Section Title ──────────────────────────── */
        /* page-break-after: avoid — tanpa ini judul section bisa jadi baris
           terakhir sendirian di bawah halaman, terpisah dari isinya di halaman
           berikutnya. DomPDF menghormati aturan ini dengan mendorong judul
           (bukan isinya) ke halaman baru kalau ruang yang tersisa tidak cukup. */
        .section-title {
            background: #f0fdf4;
            border-left: 3px solid #16a34a;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: bold;
            color: #166534;
            margin: 14px 0 6px;
            page-break-after: avoid;
        }

        /* ── Tables ─────────────────────────────────── */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
        }
        /* display: table-header-group membuat DomPDF mengulang baris header ini
           di setiap halaman baru saat isi tabel terpotong — tanpa ini, baris
           lanjutan di halaman berikutnya tidak lagi punya keterangan kolom. */
        table.data-table thead {
            display: table-header-group;
        }
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
        table.data-table tbody tr { page-break-inside: avoid; }
        table.data-table tr:last-child td { border-bottom: none; }
        table.data-table tbody tr:nth-child(even) td { background: #f9fafb; }

        /* Satu blok periode/rapor dijaga utuh dalam satu halaman selama muat —
           kalau tidak muat, seluruh blok pindah ke halaman baru, bukan terpotong
           di tengah tabel adab/kepribadian seperti sebelumnya. */
        .blok { page-break-inside: avoid; }

        /* ── Badge nilai ────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
        .badge-a { background: #dcfce7; color: #166534; }
        .badge-b { background: #dbeafe; color: #1d4ed8; }
        .badge-c { background: #fef9c3; color: #854d0e; }
        .badge-d { background: #fee2e2; color: #991b1b; }

        /* ── Catatan box ─────────────────────────────── */
        .note-box {
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 4px;
            padding: 8px 10px;
            margin-top: 4px;
            font-size: 10px;
            color: #78350f;
        }

        /*
         * left/right: 1.8cm, BUKAN 0. Footer position:fixed diposisikan relatif ke
         * kotak halaman fisik, bukan ke kotak margin <body> — jadi left/right:0
         * membuatnya menempel rata ke tepi kertas sementara seluruh konten lain
         * (kop, tabel) punya inset 1.8cm dari margin body.
         */
        .footer {
            position: fixed;
            bottom: 0;
            left: 1.8cm;
            right: 1.8cm;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }

        .periode-title {
            font-size: 10px;
            font-weight: bold;
            color: #166534;
            margin: 8px 0 3px;
            page-break-after: avoid;
        }

        .catatan-kaki {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
        }

        .no-data { color: #9ca3af; font-style: italic; font-size: 10px; }
    </style>
</head>
<body>

{{-- ── Footer (fixed) ────────────────────────────────────────────────── --}}
<div class="footer">
    Dicetak via Walisantri.com — {{ now()->timezone(config('app.display_timezone'))->translatedFormat('d M Y, H:i') }} WIB
</div>

{{-- ── Header ──────────────────────────────────────────────────────────── --}}
<div class="header">
    @if($santri->pesantren?->logo_path)
    <img src="{{ $santri->pesantren->logo_path }}" class="logo" alt="Logo">
    @endif
    @if($santri->pesantren)
    <div class="pesantren-name">{{ $santri->pesantren->nama_pesantren }}</div>
    @endif
    <div class="meta">LAPORAN PERKEMBANGAN SANTRI</div>
</div>

{{-- ── Info Santri ─────────────────────────────────────────────────────── --}}
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
            <td>: {{ $santri->nis }}</td>
            <td>Cakupan</td>
            <td>: Satu Tahun Ajaran</td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>: {{ $santri->kelas?->nama_kelas ?? '—' }}</td>
            <td>Kamar</td>
            <td>: {{ $santri->kamar?->nama_kamar ?? '—' }}</td>
        </tr>
    </table>
</div>

{{--
    Judul section sengaja TANPA emoji (beda dari versi sebelumnya): emoji-nya
    berupa glif warna (mis. U+1F4D6), dan DomPDF merender lewat font DejaVu Sans
    yang tidak punya satu pun glif emoji — hasilnya kotak/karakter acak, bukan
    ikon. Sama seperti catatan di App\Services\Rapor\RaporMutabaahData untuk
    ikon amalan, dan sejalan dengan PDF rapor panel admin yang juga tidak
    memakai emoji di judul modulnya.
--}}

{{-- ── Rapor Tahfidz ───────────────────────────────────────────────────── --}}
<div class="section-title">Rapor Tahfidz</div>
@forelse($raporTahfidz as $rapor)
<div class="blok">
    <div class="periode-title">{{ \App\Services\TahunAjaranOptions::labelPeriode($rapor->periode, $rapor->bulan, $tahunAjaran) }}</div>
    <table class="data-table">
        <thead>
        <tr>
            <th style="width:30%">Aspek Penilaian</th>
            <th style="width:15%">Nilai</th>
            <th>Keterangan</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Hafalan</td>
            <td><span class="badge" style="background:#f3f4f6;color:#1a1a1a;">{{ $rapor->nilai_hafalan }}</span></td>
            <td>Estimasi pencapaian hafalan</td>
        </tr>
        @foreach([
            'nilai_tilawah' => 'Kelancaran Tilawah',
            'nilai_makhraj' => 'Makhraj Huruf',
            'nilai_tajwid'  => 'Tajwid',
        ] as $field => $label)
        @php $val = $rapor->$field; $cls = match($val) {'A'=>'badge-a','B'=>'badge-b','C'=>'badge-c',default=>'badge-d'}; @endphp
        <tr>
            <td>{{ $label }}</td>
            <td><span class="badge {{ $cls }}">{{ $val }}</span></td>
            <td></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @if($rapor->rekomendasi_pembimbing)
    <div style="font-size:10px;color:#374151;margin-top:4px;">
        <strong>Rekomendasi Pembimbing:</strong><br>
        <em>{{ $rapor->rekomendasi_pembimbing }}</em>
    </div>
    @endif
</div>
@empty
<p class="no-data">Belum ada data rapor tahfidz untuk tahun ajaran ini.</p>
@endforelse

{{-- ── Rapor Akademik ───────────────────────────────────────────────────── --}}
<div class="section-title">Rapor Akademik</div>
@forelse($raporAkademik as $periodeKey => $nilaiList)
<div class="blok">
    <div class="periode-title">{{ \App\Services\TahunAjaranOptions::labelPeriode($periodeKey, $nilaiList->first()?->bulan, $tahunAjaran) }}</div>
    <table class="data-table">
        <thead>
        <tr>
            <th style="width:40%">Mata Pelajaran</th>
            <th style="width:15%">Nilai</th>
            <th>Catatan</th>
        </tr>
        </thead>
        <tbody>
        @foreach($nilaiList as $nilai)
        <tr>
            <td>{{ $nilai->mataPelajaran?->nama_mapel ?? '—' }}</td>
            <td><span class="badge" style="background:#f3f4f6;color:#1a1a1a;">{{ $nilai->nilai }}</span></td>
            <td>{{ $nilai->catatan ?: '—' }}</td>
        </tr>
        @endforeach
        <tr>
            <td><strong>Rata-rata</strong></td>
            <td><strong>{{ round($nilaiList->avg('nilai'), 1) }}</strong></td>
            <td></td>
        </tr>
        </tbody>
    </table>
</div>
@empty
<p class="no-data">Belum ada data rapor akademik untuk tahun ajaran ini.</p>
@endforelse

{{-- ── Rapor Karakter ──────────────────────────────────────────────────── --}}
<div class="section-title">Rapor Karakter</div>
@forelse($raporKarakter as $karakter)
<div class="blok">
    <div class="periode-title">{{ \App\Services\TahunAjaranOptions::labelPeriode($karakter->periode, $karakter->bulan, $tahunAjaran) }}</div>

    {{-- Adab --}}
    <table class="data-table">
        <thead>
        <tr>
            <th colspan="2" style="background:#065f46;">Adab</th>
        </tr>
        </thead>
        <tbody>
        @foreach(\App\Services\Rapor\RaporKarakterData::adabFields() as $field => $label)
        @php $val = $karakter->$field; $cls = match($val) {'A'=>'badge-a','B'=>'badge-b','C'=>'badge-c',default=>'badge-d'}; @endphp
        <tr>
            <td>{{ $label }}</td>
            <td style="width:60px;"><span class="badge {{ $cls }}">{{ $val }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>

    {{-- Kepribadian --}}
    <table class="data-table">
        <thead>
        <tr>
            <th colspan="2" style="background:#065f46;">Kepribadian</th>
        </tr>
        </thead>
        <tbody>
        @foreach(\App\Services\Rapor\RaporKarakterData::kepribadianFields() as $field => $label)
        @php $val = $karakter->$field; $cls = match($val) {'A'=>'badge-a','B'=>'badge-b','C'=>'badge-c',default=>'badge-d'}; @endphp
        <tr>
            <td>{{ $label }}</td>
            <td style="width:60px;"><span class="badge {{ $cls }}">{{ $val }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>

    @if($karakter->log_kasus_khusus)
    <div class="note-box">
        <strong>⚠ Catatan Khusus:</strong><br>
        {{ $karakter->log_kasus_khusus }}
    </div>
    @endif
</div>
@empty
<p class="no-data">Belum ada data rapor karakter untuk tahun ajaran ini.</p>
@endforelse

{{-- ── Ringkasan Mutaba'ah ─────────────────────────────────────────────── --}}
<div class="section-title">Ringkasan Mutaba'ah</div>
@if($raporMutabaah['ada_data'])
<div class="blok">
    @php $rr = $raporMutabaah['rata_rata']; $clsRr = $rr >= 80 ? 'badge-a' : ($rr >= 60 ? 'badge-c' : 'badge-d'); @endphp
    <table class="data-table">
        <thead>
        <tr>
            <th style="width:50%">Indikator</th>
            <th>Nilai</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Hari Tercatat</td>
            <td>{{ $raporMutabaah['total_hari'] }} hari</td>
        </tr>
        <tr>
            <td>Hari Udzur</td>
            <td>{{ $raporMutabaah['total_udzur'] }} hari</td>
        </tr>
        <tr>
            <td>Rata-rata Capaian Amalan</td>
            <td><span class="badge {{ $clsRr }}">{{ $rr }}%</span></td>
        </tr>
        </tbody>
    </table>
    <table class="data-table">
        <thead>
        <tr>
            <th style="width:40%">Amalan</th>
            <th style="width:20%">Terpenuhi</th>
            <th style="width:20%">Target</th>
            <th>Persentase</th>
        </tr>
        </thead>
        <tbody>
        @foreach($raporMutabaah['amalan'] as $item)
        @php $cls = $item['persen'] >= 80 ? 'badge-a' : ($item['persen'] >= 60 ? 'badge-c' : 'badge-d'); @endphp
        <tr>
            <td>{{ $item['label'] }}</td>
            <td>{{ $item['total_capai'] }}</td>
            <td>{{ $item['total_maks'] }}</td>
            <td><span class="badge {{ $cls }}">{{ $item['persen'] }}%</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@else
<p class="no-data">Belum ada data mutaba'ah untuk tahun ajaran ini.</p>
@endif

{{-- ── Ringkasan Kehadiran ─────────────────────────────────────────────── --}}
<div class="section-title">Ringkasan Kehadiran</div>
@if($raporPresensi['ada_data'])
<div class="blok">
    @php $persen = $raporPresensi['persen_kehadiran']; $clsPersen = $persen >= 90 ? 'badge-a' : ($persen >= 75 ? 'badge-c' : 'badge-d'); @endphp
    <table class="data-table">
        <thead>
        <tr>
            <th style="width:50%">Indikator</th>
            <th>Nilai</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Persentase Kehadiran</td>
            <td><span class="badge {{ $clsPersen }}">{{ $persen }}%</span></td>
        </tr>
        <tr>
            <td>Hari Hadir</td>
            <td>{{ $raporPresensi['hadir_efektif'] }} dari {{ $raporPresensi['hari_efektif'] }} hari efektif</td>
        </tr>
        <tr>
            <td>Tanpa Keterangan</td>
            <td>{{ $raporPresensi['tanpa_keterangan'] }} hari</td>
        </tr>
        </tbody>
    </table>
    <table class="data-table">
        <thead>
        <tr>
            <th style="width:70%">Status</th>
            <th>Jumlah Hari</th>
        </tr>
        </thead>
        <tbody>
        @foreach($raporPresensi['status'] as $item)
        <tr>
            <td>{{ $item['label'] }}</td>
            <td>{{ $item['jumlah'] }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <p class="catatan-kaki">
        Periode dihitung: {{ \Illuminate\Support\Carbon::parse($raporPresensi['awal'])->translatedFormat('d M Y') }}
        – {{ \Illuminate\Support\Carbon::parse($raporPresensi['akhir'])->translatedFormat('d M Y') }}.
        <strong>Tanpa Keterangan</strong> adalah hari efektif yang presensinya belum tercatat, bukan
        ketidakhadiran yang dinyatakan — sistem tidak pernah menandai Alpa secara otomatis.
    </p>
</div>
@else
<p class="no-data">Belum ada data presensi untuk tahun ajaran ini.</p>
@endif

{{-- ── Riwayat Setoran Tahfidz ─────────────────────────────────────────── --}}
<div class="section-title">Riwayat Setoran Tahfidz (10 Terakhir)</div>
@if($progressTahfidz->isNotEmpty())
<table class="data-table">
    <thead>
    <tr>
        <th>Tanggal</th>
        <th>Tipe</th>
        <th>Surah</th>
        <th>Halaman</th>
        <th>Nilai</th>
    </tr>
    </thead>
    <tbody>
    @foreach($progressTahfidz as $p)
    @php $nk = $p->nilai_kelancaran; $cls = match($nk) {'Mumtaz'=>'badge-a','Jayyid Jiddan'=>'badge-b','Jayyid'=>'badge-c',default=>'badge-d'}; @endphp
    <tr>
        <td>{{ $p->tanggal->format('d/m/Y') }}</td>
        <td>{{ $p->tipe_setoran }}</td>
        <td>{{ $p->nama_surah ?: '—' }}</td>
        <td>{{ $p->halaman_mulai }}–{{ $p->halaman_selesai }}</td>
        <td><span class="badge {{ $cls }}" style="font-size:9px;">{{ $nk }}</span></td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<p class="no-data">Belum ada data riwayat setoran tahun ini.</p>
@endif

</body>
</html>
