@php $rapor = $modul['rapor']; @endphp

{{-- Judul seksi tanpa emoji — lihat catatan di akademik.blade.php. --}}
<p class="catatan-inline">
    Tanggal Input: <strong>{{ $rapor->tanggal_input?->translatedFormat('d M Y') ?? '—' }}</strong>
</p>

<div class="section-title">Penilaian Adab</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width:70%">Aspek</th>
            <th>Nilai</th>
        </tr>
    </thead>
    <tbody>
    @foreach($modul['adab_fields'] as $field => $label)
    @php $val = $rapor->$field; @endphp
    <tr>
        <td>{{ $label }}</td>
        <td><span class="badge-huruf badge-{{ strtolower($val) }}">{{ $val }}</span></td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="section-title">Penilaian Kepribadian</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width:70%">Aspek</th>
            <th>Nilai</th>
        </tr>
    </thead>
    <tbody>
    @foreach($modul['kepribadian_fields'] as $field => $label)
    @php $val = $rapor->$field; @endphp
    <tr>
        <td>{{ $label }}</td>
        <td><span class="badge-huruf badge-{{ strtolower($val) }}">{{ $val }}</span></td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="keterangan-wrap">
    @foreach(['a' => 'Sangat Baik', 'b' => 'Baik', 'c' => 'Cukup', 'd' => 'Perlu Bimbingan'] as $huruf => $arti)
    {{-- Ukuran badge legenda diatur kelas .badge-huruf-kecil di layout, bukan
         style inline yang menimpa tiga properti sekaligus. --}}
    <span class="keterangan-item">
        <span class="badge-huruf badge-huruf-kecil badge-{{ $huruf }}">{{ strtoupper($huruf) }}</span>
        {{ $arti }}
    </span>
    @endforeach
</div>

@if($rapor->log_kasus_khusus)
<div class="section-title">Log Kasus Khusus</div>
<div class="catatan-box">{{ $rapor->log_kasus_khusus }}</div>
@endif
