@php $rapor = $modul['rapor']; @endphp

<p style="font-size:10px; margin-bottom:8px;">
    Tanggal Input: <strong>{{ $rapor->tanggal_input?->translatedFormat('d M Y') ?? '—' }}</strong>
</p>

<div class="section-title">🕌 Penilaian Adab</div>
<table class="data-table">
    <tr>
        <th style="width:70%">Aspek</th>
        <th>Nilai</th>
    </tr>
    @foreach($modul['adab_fields'] as $field => $label)
    @php $val = $rapor->$field; @endphp
    <tr>
        <td>{{ $label }}</td>
        <td><span class="badge-huruf badge-{{ strtolower($val) }}">{{ $val }}</span></td>
    </tr>
    @endforeach
</table>

<div class="section-title">🌟 Penilaian Kepribadian</div>
<table class="data-table">
    <tr>
        <th style="width:70%">Aspek</th>
        <th>Nilai</th>
    </tr>
    @foreach($modul['kepribadian_fields'] as $field => $label)
    @php $val = $rapor->$field; @endphp
    <tr>
        <td>{{ $label }}</td>
        <td><span class="badge-huruf badge-{{ strtolower($val) }}">{{ $val }}</span></td>
    </tr>
    @endforeach
</table>

<div class="keterangan-wrap">
    @foreach(['a' => 'Sangat Baik', 'b' => 'Baik', 'c' => 'Cukup', 'd' => 'Perlu Bimbingan'] as $huruf => $arti)
    <span class="keterangan-item">
        <span class="badge-huruf badge-{{ $huruf }}" style="font-size:9px;width:16px;height:16px;line-height:16px">{{ strtoupper($huruf) }}</span>
        {{ $arti }}
    </span>
    @endforeach
</div>

@if($rapor->log_kasus_khusus)
<div class="section-title">📝 Log Kasus Khusus</div>
<div class="catatan-box">{{ $rapor->log_kasus_khusus }}</div>
@endif
