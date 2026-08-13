@php $stats = $modul['setoran_stats']; @endphp

<p style="font-size:10px; margin-bottom:8px;">Capaian Juz (Lulus): <strong>{{ $modul['total_juz_lulus'] }} Juz</strong></p>

<div class="section-title">📈 Ringkasan Setoran Periode Ini</div>
@if($stats['total_setoran'] === 0)
    <p class="no-data">Belum ada setoran pada periode ini.</p>
@else
<table class="stat-grid">
    <tr>
        <td>
            <span class="stat-label">Total Setoran</span>
            <span class="stat-value">{{ $stats['total_setoran'] }}</span>
        </td>
        <td>
            <span class="stat-label">Total Juz</span>
            <span class="stat-value">{{ $stats['total_juz'] }}</span>
        </td>
        <td>
            <span class="stat-label">Hari Aktif</span>
            <span class="stat-value">{{ $stats['hari_aktif'] }}</span>
        </td>
        @foreach($stats['per_tipe'] as $tipe => $detail)
        <td>
            <span class="stat-label">{{ $tipe }}</span>
            <span class="stat-value">{{ $detail['jumlah'] }}</span>
            <span class="stat-label">{{ $detail['halaman'] }} hal.</span>
        </td>
        @endforeach
    </tr>
</table>

@if($stats['nilai_distribusi']->isNotEmpty())
<p style="font-size:10px; font-weight:bold; color:#374151; margin-bottom:4px;">Distribusi Nilai Kelancaran</p>
@foreach(['Mumtaz', 'Jayyid Jiddan', 'Jayyid', 'Maqbul'] as $label)
@php
    $cnt = $stats['nilai_distribusi'][$label] ?? 0;
    $pct = $cnt > 0 ? round($cnt / $stats['total_setoran'] * 100) : 0;
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

@if($stats['surah_list']->isNotEmpty())
<p style="margin-bottom: 10px;">
    @foreach($stats['surah_list'] as $surah)
        <span class="surah-tag">{{ $surah }}</span>
    @endforeach
</p>
@endif
@endif

<div class="section-title">📖 Hasil Ujian Tahfidz</div>
@if($modul['ujian']->isEmpty())
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
    @foreach($modul['ujian'] as $ujian)
    @php $clsStatus = $ujian->status_kelulusan === 'Lulus' ? 'badge-hijau' : 'badge-kuning'; @endphp
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
