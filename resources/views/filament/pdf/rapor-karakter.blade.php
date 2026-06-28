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

        table.nilai-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
        }
        table.nilai-table th {
            background: #166534;
            color: #fff;
            padding: 5px 8px;
            text-align: left;
        }
        table.nilai-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.nilai-table tr:last-child td { border-bottom: none; }
        table.nilai-table tr:nth-child(even) td { background: #f9fafb; }

        .badge {
            display: inline-block;
            width: 22px;
            height: 22px;
            line-height: 22px;
            text-align: center;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
        }
        .badge-a { background: #dcfce7; color: #166534; }
        .badge-b { background: #dbeafe; color: #1d4ed8; }
        .badge-c { background: #fef9c3; color: #854d0e; }
        .badge-d { background: #fee2e2; color: #991b1b; }

        .keterangan-wrap { margin-top: 8px; }
        .keterangan-item {
            display: inline-block;
            margin-right: 16px;
            font-size: 9px;
            color: #374151;
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
    </style>
</head>
<body>

<div class="footer">
    Dicetak via Walisantri.com — {{ now()->translatedFormat('d M Y, H:i') }} WIB
</div>

<div class="header">
    <div class="app-name">Walisantri.com</div>
    @if($santri->pesantren)
    <div class="pesantren-name">{{ $santri->pesantren->nama_pesantren }}</div>
    @endif
    <div class="meta">RAPOR KARAKTER SANTRI</div>
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
            <td>: {{ $periodeLabel }}{{ $bulanLabel ? ' — ' . $bulanLabel : '' }}</td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>: {{ $santri->kelas?->nama_kelas ?? '—' }}</td>
            <td>Tanggal Input</td>
            <td>: {{ $rapor->tanggal_input?->translatedFormat('d M Y') ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- Adab --}}
<div class="section-title">🕌 Penilaian Adab</div>
<table class="nilai-table">
    <tr>
        <th style="width:70%">Aspek</th>
        <th>Nilai</th>
    </tr>
    @foreach($adabFields as $field => $label)
    @php $val = $rapor->$field; $cls = 'badge-' . strtolower($val); @endphp
    <tr>
        <td>{{ $label }}</td>
        <td><span class="badge {{ $cls }}">{{ $val }}</span></td>
    </tr>
    @endforeach
</table>

{{-- Kepribadian --}}
<div class="section-title">🌟 Penilaian Kepribadian</div>
<table class="nilai-table">
    <tr>
        <th style="width:70%">Aspek</th>
        <th>Nilai</th>
    </tr>
    @foreach($kepFields as $field => $label)
    @php $val = $rapor->$field; $cls = 'badge-' . strtolower($val); @endphp
    <tr>
        <td>{{ $label }}</td>
        <td><span class="badge {{ $cls }}">{{ $val }}</span></td>
    </tr>
    @endforeach
</table>

<div class="keterangan-wrap">
    <span class="keterangan-item"><span class="badge badge-a" style="font-size:9px;width:16px;height:16px;line-height:16px">A</span> Sangat Baik</span>
    <span class="keterangan-item"><span class="badge badge-b" style="font-size:9px;width:16px;height:16px;line-height:16px">B</span> Baik</span>
    <span class="keterangan-item"><span class="badge badge-c" style="font-size:9px;width:16px;height:16px;line-height:16px">C</span> Cukup</span>
    <span class="keterangan-item"><span class="badge badge-d" style="font-size:9px;width:16px;height:16px;line-height:16px">D</span> Perlu Bimbingan</span>
</div>

@if($rapor->log_kasus_khusus)
<div class="section-title">📝 Log Kasus Khusus</div>
<div class="catatan-box">{{ $rapor->log_kasus_khusus }}</div>
@endif

</body>
</html>
