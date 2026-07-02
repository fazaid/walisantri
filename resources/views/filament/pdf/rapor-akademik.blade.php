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
    <div class="meta">NILAI AKADEMIK</div>
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
            <td>Rata-rata</td>
            <td>: <strong>{{ $rataRata }}</strong></td>
        </tr>
    </table>
</div>

<div class="section-title">📚 Nilai per Mata Pelajaran</div>
@if($nilai->isEmpty())
    <p class="no-data">Belum ada nilai akademik pada periode ini.</p>
@else
<table class="data-table">
    <tr>
        <th style="width:45%">Mata Pelajaran</th>
        <th style="width:15%">Nilai</th>
        <th>Catatan</th>
    </tr>
    @foreach($nilai as $item)
    @php
        $skor = $item->nilai;
        $cls = match (true) {
            $skor >= 85 => 'badge-hijau',
            $skor >= 70 => 'badge-biru',
            $skor >= 60 => 'badge-kuning',
            default     => 'badge-merah',
        };
    @endphp
    <tr>
        <td>{{ $item->mataPelajaran?->nama_mapel ?? '—' }}</td>
        <td><span class="badge {{ $cls }}">{{ $skor }}</span></td>
        <td>{{ $item->catatan ?: '—' }}</td>
    </tr>
    @endforeach
</table>
@endif

</body>
</html>
