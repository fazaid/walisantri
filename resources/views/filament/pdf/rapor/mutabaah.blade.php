@php
    $rr = $modul['rata_rata'];
@endphp

<div class="section-title">Statistik Kehadiran</div>
<table class="stat-grid-4">
    <tr>
        <td>
            <div class="stat-num">{{ $modul['total_hari'] }}</div>
            <div class="stat-label">Hari Tercatat</div>
        </td>
        <td>
            <div class="stat-num">{{ $modul['total_hari'] - $modul['total_udzur'] }}</div>
            <div class="stat-label">Hari Aktif</div>
        </td>
        <td>
            <div class="stat-num" style="color:#ea580c">{{ $modul['total_udzur'] }}</div>
            <div class="stat-label">Hari Udzur</div>
        </td>
        <td>
            <div class="stat-num" style="color:{{ $rr >= 80 ? '#16a34a' : ($rr >= 60 ? '#ca8a04' : '#dc2626') }}">{{ $rr }}%</div>
            <div class="stat-label">Rata-rata</div>
        </td>
    </tr>
</table>

<div class="section-title">Ringkasan Amalan Harian</div>
@if(empty($modul['amalan']))
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
    @foreach($modul['amalan'] as $item)
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

@if(! empty($modul['udzur_detail']))
<div class="section-title">Rincian Udzur</div>
<table class="data-table">
    <tr>
        <th>Jenis Udzur</th>
        <th style="width:20%">Jumlah Hari</th>
    </tr>
    @foreach($modul['udzur_detail'] as $jenis => $jumlah)
    <tr>
        <td>{{ str_replace('_', ' ', $jenis) }}</td>
        <td><span class="badge badge-oranye">{{ $jumlah }} hari</span></td>
    </tr>
    @endforeach
</table>
@endif
