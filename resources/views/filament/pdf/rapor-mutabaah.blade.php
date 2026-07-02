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

        .stat-grid {
            width: 100%;
            margin-bottom: 14px;
        }
        .stat-grid td {
            width: 25%;
            text-align: center;
            border: 1px solid #e5e7eb;
            padding: 8px;
            border-radius: 4px;
        }
        .stat-num { font-size: 20px; font-weight: bold; color: #166534; }
        .stat-label { font-size: 9px; color: #6b7280; margin-top: 2px; }

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

        .badge {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
        .badge-hijau  { background: #dcfce7; color: #166534; }
        .badge-kuning { background: #fef9c3; color: #854d0e; }
        .badge-merah  { background: #fee2e2; color: #991b1b; }
        .badge-oranye { background: #ffedd5; color: #9a3412; }

        .progress-bar-wrap {
            background: #e5e7eb;
            border-radius: 3px;
            height: 8px;
            width: 80px;
            display: inline-block;
            vertical-align: middle;
        }
        .progress-bar-fill {
            height: 8px;
            border-radius: 3px;
        }
        .bar-hijau  { background: #16a34a; }
        .bar-kuning { background: #ca8a04; }
        .bar-merah  { background: #dc2626; }

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
    <div class="meta">RAPOR MUTABAAH HARIAN</div>
</div>

<div class="info-card">
    <table>
        <tr>
            <td>Nama Santri</td>
            <td>: <strong>{{ $santri->nama_lengkap }}</strong></td>
            <td>Periode</td>
            <td>: {{ $bulan }} {{ $tahun }}</td>
        </tr>
        <tr>
            <td>NIS</td>
            <td>: {{ $santri->nis ?? '—' }}</td>
            <td>Hari Tercatat</td>
            <td>: {{ $ringkasan['total_hari'] }} hari</td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>: {{ $santri->kelas?->nama_kelas ?? '—' }}</td>
            <td>Rata-rata Kepatuhan</td>
            @php
                $rr = $ringkasan['rata_rata'];
                $rrCls = $rr >= 80 ? 'badge-hijau' : ($rr >= 60 ? 'badge-kuning' : 'badge-merah');
            @endphp
            <td>: <span class="badge {{ $rrCls }}">{{ $rr }}%</span></td>
        </tr>
    </table>
</div>

{{-- Statistik Hari --}}
<div class="section-title">Statistik Kehadiran</div>
<table class="stat-grid">
    <tr>
        <td>
            <div class="stat-num">{{ $ringkasan['total_hari'] }}</div>
            <div class="stat-label">Hari Tercatat</div>
        </td>
        <td>
            <div class="stat-num">{{ $ringkasan['total_hari'] - $ringkasan['total_udzur'] }}</div>
            <div class="stat-label">Hari Aktif</div>
        </td>
        <td>
            <div class="stat-num" style="color:#ea580c">{{ $ringkasan['total_udzur'] }}</div>
            <div class="stat-label">Hari Udzur</div>
        </td>
        <td>
            <div class="stat-num" style="color:{{ $rr >= 80 ? '#16a34a' : ($rr >= 60 ? '#ca8a04' : '#dc2626') }}">{{ $rr }}%</div>
            <div class="stat-label">Rata-rata</div>
        </td>
    </tr>
</table>

{{-- Ringkasan Amalan --}}
<div class="section-title">Ringkasan Amalan Harian</div>
@if(empty($ringkasan['amalan']))
    <p class="no-data">Tidak ada data amalan.</p>
@else
<table class="data-table">
    <tr>
        <th style="width:40%">Amalan</th>
        <th style="width:12%" class="text-center">Terpenuhi</th>
        <th style="width:12%" class="text-center">Target</th>
        <th style="width:15%" class="text-center">Persentase</th>
        <th>Progress</th>
    </tr>
    @foreach($ringkasan['amalan'] as $item)
    @php
        $persen = $item['persen'];
        $cls = $persen >= 80 ? 'badge-hijau' : ($persen >= 60 ? 'badge-kuning' : 'badge-merah');
        $barCls = $persen >= 80 ? 'bar-hijau' : ($persen >= 60 ? 'bar-kuning' : 'bar-merah');
    @endphp
    <tr>
        <td>{{ $item['label'] }}</td>
        <td style="text-align:center">{{ $item['total_capai'] }}</td>
        <td style="text-align:center">{{ $item['total_maks'] }}</td>
        <td style="text-align:center"><span class="badge {{ $cls }}">{{ $persen }}%</span></td>
        <td>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill {{ $barCls }}" style="width:{{ $persen }}%"></div>
            </div>
        </td>
    </tr>
    @endforeach
</table>
@endif

{{-- Udzur --}}
@if(! empty($ringkasan['udzur_detail']))
<div class="section-title">Rincian Udzur</div>
<table class="data-table">
    <tr>
        <th>Jenis Udzur</th>
        <th style="width:20%">Jumlah Hari</th>
    </tr>
    @foreach($ringkasan['udzur_detail'] as $jenis => $jumlah)
    <tr>
        <td>{{ str_replace('_', ' ', $jenis) }}</td>
        <td><span class="badge badge-oranye">{{ $jumlah }} hari</span></td>
    </tr>
    @endforeach
</table>
@endif

</body>
</html>
