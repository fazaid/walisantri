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

        .stat-grid {
            width: 100%;
            margin-bottom: 10px;
        }
        .stat-grid td {
            text-align: center;
            padding: 8px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        .stat-grid .stat-label { display: block; font-size: 9px; color: #6b7280; margin-bottom: 2px; }
        .stat-grid .stat-value { display: block; font-size: 14px; font-weight: bold; color: #1a1a1a; }

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

        .surah-tag {
            display: inline-block;
            padding: 2px 8px;
            margin: 2px;
            border-radius: 10px;
            background: #f0fdf4;
            color: #166534;
            font-size: 9px;
        }

        .distribusi-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }
        .distribusi-row .d-label {
            display: table-cell;
            width: 110px;
            font-size: 10px;
            color: #374151;
        }
        .distribusi-row .d-value {
            display: table-cell;
            width: 70px;
            font-size: 10px;
            color: #1a1a1a;
            font-weight: bold;
            text-align: right;
        }

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

<div class="footer">
    Dicetak via Walisantri.com — {{ now()->translatedFormat('d M Y, H:i') }} WIB
</div>

<div class="header">
    @if($santri->pesantren?->logo_path)
    <img src="{{ $santri->pesantren->logo_path }}" class="logo" alt="Logo">
    @endif
    <div class="app-name">Walisantri.com</div>
    @if($santri->pesantren)
    <div class="pesantren-name">{{ $santri->pesantren->nama_pesantren }}</div>
    @endif
    <div class="meta">RAPOR TAHFIDZ</div>
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
            <td>: {{ $santri->nis }}</td>
            <td>Periode</td>
            <td>: {{ str_replace('_', ' ', $periode) }}</td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>: {{ $santri->kelas?->nama_kelas ?? '—' }}</td>
            <td>Capaian Juz (Lulus)</td>
            <td>: <strong>{{ $totalJuzLulus }} Juz</strong></td>
        </tr>
    </table>
</div>

<div class="section-title">📈 Ringkasan Setoran Periode Ini</div>
@if($setoranStats['total_setoran'] === 0)
    <p class="no-data">Belum ada setoran pada periode ini.</p>
@else
<table class="stat-grid">
    <tr>
        <td>
            <span class="stat-label">Total Setoran</span>
            <span class="stat-value">{{ $setoranStats['total_setoran'] }}</span>
        </td>
        <td>
            <span class="stat-label">Total Halaman</span>
            <span class="stat-value">{{ $setoranStats['total_halaman'] }}</span>
        </td>
        <td>
            <span class="stat-label">Hari Aktif</span>
            <span class="stat-value">{{ $setoranStats['hari_aktif'] }}</span>
        </td>
        @foreach($setoranStats['per_tipe'] as $tipe => $data)
        <td>
            <span class="stat-label">{{ $tipe }}</span>
            <span class="stat-value">{{ $data['jumlah'] }}</span>
            <span class="stat-label">{{ $data['halaman'] }} hal.</span>
        </td>
        @endforeach
    </tr>
</table>

@if($setoranStats['nilai_distribusi']->isNotEmpty())
<p style="font-size:10px; font-weight:bold; color:#374151; margin-bottom:4px;">Distribusi Nilai Kelancaran</p>
@foreach(['Mumtaz', 'Jayyid Jiddan', 'Jayyid', 'Maqbul'] as $label)
@php
    $cnt = $setoranStats['nilai_distribusi'][$label] ?? 0;
    $pct = $cnt > 0 ? round($cnt / $setoranStats['total_setoran'] * 100) : 0;
@endphp
@if($cnt > 0)
<div class="distribusi-row">
    <span class="d-label">{{ $label }}</span>
    <span class="d-value">{{ $cnt }} ({{ $pct }}%)</span>
</div>
@endif
@endforeach
<div style="margin-bottom: 10px;"></div>
@endif

@if($setoranStats['surah_list']->isNotEmpty())
<p style="margin-bottom: 10px;">
    @foreach($setoranStats['surah_list'] as $surah)
        <span class="surah-tag">{{ $surah }}</span>
    @endforeach
</p>
@endif
@endif

<div class="section-title">📖 Hasil Ujian Tahfidz</div>
@if($ujianList->isEmpty())
    <p class="no-data">Belum ada ujian tahfidz pada periode ini.</p>
@else
<table class="data-table">
    <tr>
        <th style="width:9%">Tanggal</th>
        <th style="width:7%">Juz</th>
        <th style="width:8%">Status</th>
        <th style="width:7%">Hafalan</th>
        <th style="width:6%">Tilawah</th>
        <th style="width:6%">Makhraj</th>
        <th style="width:6%">Tajwid</th>
        <th style="width:13%">Penguji</th>
        <th>Rekomendasi</th>
    </tr>
    @foreach($ujianList as $ujian)
    @php
        $clsStatus = $ujian->status_kelulusan === 'Lulus' ? 'badge-hijau' : 'badge-kuning';
    @endphp
    <tr>
        <td>{{ $ujian->tanggal_ujian?->translatedFormat('d M Y') ?? '—' }}</td>
        <td>{{ $ujian->target_juz ?? '—' }}</td>
        <td><span class="badge {{ $clsStatus }}">{{ $ujian->status_kelulusan ?? '—' }}</span></td>
        <td>{{ $ujian->nilai_hafalan }}</td>
        <td>{{ $ujian->nilai_tilawah }}</td>
        <td>{{ $ujian->nilai_makhraj }}</td>
        <td>{{ $ujian->nilai_tajwid }}</td>
        <td>{{ $ujian->penguji?->name ?? '—' }}</td>
        <td>{{ $ujian->rekomendasi_pembimbing ?: '—' }}</td>
    </tr>
    @endforeach
</table>
@endif

</body>
</html>
