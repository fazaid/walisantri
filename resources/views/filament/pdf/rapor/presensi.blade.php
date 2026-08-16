@php
    $persen = $modul['persen_kehadiran'];
    $warnaPersen = $persen >= 90 ? '#16a34a' : ($persen >= 75 ? '#ca8a04' : '#dc2626');
@endphp

<div class="section-title">Ringkasan Kehadiran</div>
<table class="stat-grid-4">
    <tr>
        <td>
            <div class="stat-num" style="color:{{ $warnaPersen }}">{{ $persen }}%</div>
            <div class="stat-label">Kehadiran</div>
        </td>
        <td>
            <div class="stat-num">{{ $modul['hadir_efektif'] }}</div>
            <div class="stat-label">Hari Hadir</div>
        </td>
        <td>
            <div class="stat-num">{{ $modul['hari_efektif'] }}</div>
            <div class="stat-label">Hari Efektif</div>
        </td>
        <td>
            <div class="stat-num" style="color:#ea580c">{{ $modul['tanpa_keterangan'] }}</div>
            <div class="stat-label">Tanpa Keterangan</div>
        </td>
    </tr>
</table>

{{--
    Rentangnya dicetak apa adanya, dan itu perlu: batas atas dipotong ke hari ini
    (PresensiRekap::batasAkhir), sehingga rapor semester yang dicetak di tengah
    jalan tidak boleh terbaca seolah mencakup periode penuh.
--}}
<p class="no-data" style="font-style:normal">
    Periode dihitung: {{ \Illuminate\Support\Carbon::parse($modul['awal'])->translatedFormat('d M Y') }}
    – {{ \Illuminate\Support\Carbon::parse($modul['akhir'])->translatedFormat('d M Y') }}.
    Hari efektif tidak menghitung hari libur pondok. Hadir, Terlambat, dan Dispensasi
    dihitung sebagai hadir.
</p>

<div class="section-title">Rincian per Status</div>
@if(empty($modul['status']))
    <p class="no-data">Tidak ada data presensi.</p>
@else
<table class="data-table">
    <tr>
        <th style="width:60%">Status</th>
        <th style="width:20%" class="text-center">Jumlah Hari</th>
        <th style="width:20%" class="text-center">Porsi</th>
    </tr>
    @foreach($modul['status'] as $item)
    @php
        $porsi = $modul['hari_efektif'] > 0
            ? (int) round($item['jumlah'] / $modul['hari_efektif'] * 100)
            : 0;
    @endphp
    <tr>
        <td>{{ $item['label'] }}</td>
        <td style="text-align:center">{{ $item['jumlah'] }}</td>
        <td style="text-align:center">{{ $porsi }}%</td>
    </tr>
    @endforeach
    @if($modul['tanpa_keterangan'] > 0)
    <tr>
        <td>Tanpa Keterangan</td>
        <td style="text-align:center">{{ $modul['tanpa_keterangan'] }}</td>
        <td style="text-align:center">
            {{ $modul['hari_efektif'] > 0 ? (int) round($modul['tanpa_keterangan'] / $modul['hari_efektif'] * 100) : 0 }}%
        </td>
    </tr>
    @endif
</table>

{{--
    "Tanpa Keterangan" sengaja dipisah dari Alpa, dan kalimat ini wajib ikut
    tercetak. Sistem ini tidak pernah menandai Alpa otomatis (§11): baris itu
    berarti hari efektif yang catatannya belum diisi, yang bisa jadi kelalaian
    pencatatan — bukan bukti santri tidak masuk. Rapor adalah dokumen yang dibaca
    orang tua dan disimpan bertahun-tahun; ambiguitasnya harus dijelaskan di
    lembar yang sama, bukan diserahkan ke ingatan wali kelas.
--}}
<p class="no-data" style="font-style:normal">
    <strong>Tanpa Keterangan</strong> adalah hari efektif yang presensinya belum tercatat —
    bukan ketidakhadiran yang dinyatakan. Sistem tidak pernah menandai Alpa secara otomatis.
</p>
@endif
