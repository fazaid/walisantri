@php $stats = $modul['setoran_stats']; @endphp

{{-- Judul seksi tanpa emoji — lihat catatan di akademik.blade.php. --}}
<div class="angka-kunci">Capaian Juz (Lulus): <strong>{{ $modul['total_juz_lulus'] }} Juz</strong></div>

<div class="section-title">Ringkasan Setoran Periode Ini</div>
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
<p class="sub-judul">Distribusi Nilai Kelancaran</p>
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
<div class="jarak-bawah"></div>
@endif

@if($stats['surah_list']->isNotEmpty())
<p class="sub-judul">Surah yang Disetorkan</p>
<p class="jarak-bawah">
    @foreach($stats['surah_list'] as $surah)
        <span class="surah-tag">{{ $surah }}</span>
    @endforeach
</p>
@endif
@endif

<div class="section-title">Hasil Ujian Tahfidz</div>
@if($modul['ujian']->isEmpty())
    <p class="no-data">Belum ada ujian tahfidz pada periode ini.</p>
@else
{{--
    Delapan kolom, bukan sembilan. Versi sebelumnya memberi Penguji kolomnya
    sendiri selebar 13% di samping empat kolom nilai selebar 6-7% — di A4 portrait
    dengan font 10px, keempat angka itu jadi berdesakan sementara nama penguji
    tetap terpotong. Penguji sekarang menumpang di bawah rekomendasi: ia keterangan
    tentang siapa yang menilai, bukan angka yang perlu dibandingkan antar baris.
--}}
<table class="data-table">
    <thead>
        <tr>
            <th style="width:11%">Tanggal</th>
            <th style="width:6%" class="text-center">Juz</th>
            <th style="width:11%">Status</th>
            <th style="width:8%" class="text-center">Hafalan</th>
            <th style="width:8%" class="text-center">Tilawah</th>
            <th style="width:8%" class="text-center">Makhraj</th>
            <th style="width:8%" class="text-center">Tajwid</th>
            <th>Rekomendasi</th>
        </tr>
    </thead>
    <tbody>
    @foreach($modul['ujian'] as $ujian)
    @php $clsStatus = $ujian->status_kelulusan === 'Lulus' ? 'badge-hijau' : 'badge-kuning'; @endphp
    <tr>
        <td>{{ $ujian->tanggal_ujian?->translatedFormat('d M Y') ?? '—' }}</td>
        <td class="text-center">{{ $ujian->target_juz ?? '—' }}</td>
        <td><span class="badge {{ $clsStatus }}">{{ $ujian->status_kelulusan ?? '—' }}</span></td>
        <td class="text-center">{{ $ujian->nilai_hafalan }}</td>
        <td class="text-center">{{ $ujian->nilai_tilawah }}</td>
        <td class="text-center">{{ $ujian->nilai_makhraj }}</td>
        <td class="text-center">{{ $ujian->nilai_tajwid }}</td>
        <td>
            {{ $ujian->rekomendasi_pembimbing ?: '—' }}
            @if($ujian->penguji?->name)
                <div class="penguji">Penguji: {{ $ujian->penguji->name }}</div>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif
