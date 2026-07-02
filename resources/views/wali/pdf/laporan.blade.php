<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
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
        .header .app-name {
            font-size: 18px;
            font-weight: bold;
            color: #166534;
        }
        .header .pesantren-name {
            font-size: 13px;
            font-weight: bold;
            color: #1a1a1a;
            margin-top: 2px;
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
        .section-title {
            background: #f0fdf4;
            border-left: 3px solid #16a34a;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: bold;
            color: #166534;
            margin: 14px 0 6px;
        }

        /* ── Tables ─────────────────────────────────── */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
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
        table.data-table tr:last-child td { border-bottom: none; }
        table.data-table tr:nth-child(even) td { background: #f9fafb; }

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

        /* ── Footer ──────────────────────────────────── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }

        .no-data { color: #9ca3af; font-style: italic; font-size: 10px; }
    </style>
</head>
<body>

{{-- ── Footer (fixed) ────────────────────────────────────────────────── --}}
<div class="footer">
    Dicetak via Walisantri.com — {{ now()->translatedFormat('d M Y, H:i') }} WIB
</div>

{{-- ── Header ──────────────────────────────────────────────────────────── --}}
<div class="header">
    @if($santri->pesantren?->logo_path)
    <img src="{{ $santri->pesantren->logo_path }}" class="logo" alt="Logo">
    @endif
    <div class="app-name">Walisantri.com</div>
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
            <td>Periode</td>
            <td>: {{ str_replace('_', ' ', $periode) }}</td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>: {{ $santri->kelas?->nama_kelas ?? '—' }}</td>
            <td>Kamar</td>
            <td>: {{ $santri->kamar?->nama_kamar ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- ── Rapor Tahfidz ───────────────────────────────────────────────────── --}}
<div class="section-title">📖 Rapor Tahfidz</div>
@if($raporTahfidz)
<table class="data-table">
    <tr>
        <th style="width:30%">Aspek Penilaian</th>
        <th style="width:15%">Nilai</th>
        <th>Keterangan</th>
    </tr>
    <tr>
        <td>Hafalan</td>
        <td><span class="badge" style="background:#f3f4f6;color:#1a1a1a;">{{ $raporTahfidz->nilai_hafalan }}</span></td>
        <td>Estimasi pencapaian hafalan</td>
    </tr>
    @foreach([
        'nilai_tilawah' => 'Kelancaran Tilawah',
        'nilai_makhraj' => 'Makhraj Huruf',
        'nilai_tajwid'  => 'Tajwid',
    ] as $field => $label)
    @php $val = $raporTahfidz->$field; $cls = match($val) {'A'=>'badge-a','B'=>'badge-b','C'=>'badge-c',default=>'badge-d'}; @endphp
    <tr>
        <td>{{ $label }}</td>
        <td><span class="badge {{ $cls }}">{{ $val }}</span></td>
        <td></td>
    </tr>
    @endforeach
</table>
@if($raporTahfidz->rekomendasi_pembimbing)
<div style="font-size:10px;color:#374151;margin-top:4px;">
    <strong>Rekomendasi Pembimbing:</strong><br>
    <em>{{ $raporTahfidz->rekomendasi_pembimbing }}</em>
</div>
@endif
@else
<p class="no-data">Belum ada data rapor tahfidz untuk periode ini.</p>
@endif

{{-- ── Rapor Akademik ───────────────────────────────────────────────────── --}}
<div class="section-title">📚 Rapor Akademik</div>
@if($raporAkademik->isNotEmpty())
<table class="data-table">
    <tr>
        <th style="width:40%">Mata Pelajaran</th>
        <th style="width:15%">Nilai</th>
        <th>Catatan</th>
    </tr>
    @foreach($raporAkademik as $nilai)
    <tr>
        <td>{{ $nilai->mataPelajaran?->nama_mapel ?? '—' }}</td>
        <td><span class="badge" style="background:#f3f4f6;color:#1a1a1a;">{{ $nilai->nilai }}</span></td>
        <td>{{ $nilai->catatan ?: '—' }}</td>
    </tr>
    @endforeach
    <tr>
        <td><strong>Rata-rata</strong></td>
        <td><strong>{{ round($raporAkademik->avg('nilai'), 1) }}</strong></td>
        <td></td>
    </tr>
</table>
@else
<p class="no-data">Belum ada data rapor akademik untuk periode ini.</p>
@endif

{{-- ── Rapor Karakter ──────────────────────────────────────────────────── --}}
<div class="section-title">🌱 Rapor Karakter</div>
@if($raporKarakter)

{{-- Adab --}}
<table class="data-table">
    <tr>
        <th colspan="2" style="background:#065f46;">Adab</th>
    </tr>
    @foreach([
        'adab_ustadz' => 'Adab kepada Ustadz',
        'adab_tamu'   => 'Adab kepada Tamu',
        'adab_asrama' => 'Adab di Asrama',
        'adab_kelas'  => 'Adab di Kelas',
        'adab_sholat' => 'Adab Sholat',
        'adab_quran'  => 'Adab Al-Quran',
        'adab_minum'  => 'Adab Minum',
    ] as $field => $label)
    @php $val = $raporKarakter->$field; $cls = match($val) {'A'=>'badge-a','B'=>'badge-b','C'=>'badge-c',default=>'badge-d'}; @endphp
    <tr>
        <td>{{ $label }}</td>
        <td style="width:60px;"><span class="badge {{ $cls }}">{{ $val }}</span></td>
    </tr>
    @endforeach
</table>

{{-- Kepribadian --}}
<table class="data-table">
    <tr>
        <th colspan="2" style="background:#065f46;">Kepribadian</th>
    </tr>
    @foreach([
        'kepribadian_tanggungjawab' => 'Tanggung Jawab',
        'kepribadian_kemandirian'   => 'Kemandirian',
        'kepribadian_kepatuhan'     => 'Kepatuhan',
        'kepribadian_kebersihan'    => 'Kebersihan',
        'kepribadian_mengelola'     => 'Mengelola Diri',
        'kepribadian_kepedulian'    => 'Kepedulian',
        'kepribadian_empati'        => 'Empati',
        'kepribadian_kebersamaan'   => 'Kebersamaan',
        'kepribadian_kedisiplinan'  => 'Kedisiplinan',
    ] as $field => $label)
    @php $val = $raporKarakter->$field; $cls = match($val) {'A'=>'badge-a','B'=>'badge-b','C'=>'badge-c',default=>'badge-d'}; @endphp
    <tr>
        <td>{{ $label }}</td>
        <td style="width:60px;"><span class="badge {{ $cls }}">{{ $val }}</span></td>
    </tr>
    @endforeach
</table>

@if($raporKarakter->log_kasus_khusus)
<div class="note-box">
    <strong>⚠ Catatan Khusus:</strong><br>
    {{ $raporKarakter->log_kasus_khusus }}
</div>
@endif

@else
<p class="no-data">Belum ada data rapor karakter untuk periode ini.</p>
@endif

{{-- ── Riwayat Setoran Tahfidz ─────────────────────────────────────────── --}}
<div class="section-title">📝 Riwayat Setoran Tahfidz (10 Terakhir)</div>
@if($progressTahfidz->isNotEmpty())
<table class="data-table">
    <tr>
        <th>Tanggal</th>
        <th>Tipe</th>
        <th>Surah</th>
        <th>Halaman</th>
        <th>Nilai</th>
    </tr>
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
</table>
@else
<p class="no-data">Belum ada data riwayat setoran tahun ini.</p>
@endif

</body>
</html>
