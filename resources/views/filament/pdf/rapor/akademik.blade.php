{{-- Judul seksi TANPA emoji: PDF ini dirender DomPDF dengan DejaVu Sans, dan font
     itu tidak punya satu pun glif emoji — yang tercetak kotak kosong, bukan ikon.
     Versi layarnya (pages/partials/rapor/akademik.blade.php) tetap memakainya. --}}
<div class="section-title">Nilai per Mata Pelajaran</div>
@if($modul['nilai']->isEmpty())
    <p class="no-data">Belum ada nilai akademik pada periode ini.</p>
@else
{{-- Rata-rata dinaikkan ke ATAS tabel dan diberi bobot visual. Sebelumnya ia
     paragraf 10px di bawah tabel — angka yang paling dicari orang tua justru yang
     paling tidak terlihat, sementara modul lain punya kartu statistik besar. --}}
<div class="angka-kunci">Rata-rata Nilai: <strong>{{ $modul['rata_rata'] }}</strong></div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width:45%">Mata Pelajaran</th>
            <th style="width:15%">Nilai</th>
            <th>Catatan</th>
        </tr>
    </thead>
    <tbody>
    @foreach($modul['nilai'] as $item)
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
    </tbody>
</table>
@endif

<div class="section-title">Ekstrakurikuler Aktif</div>
@if($modul['ekskul']->isEmpty())
    <p class="no-data">Belum ada ekskul yang diikuti.</p>
@else
<table class="data-table">
    <thead>
        <tr>
            <th style="width:50%">Ekskul</th>
            <th style="width:25%">Level</th>
            <th>Mulai</th>
        </tr>
    </thead>
    <tbody>
    @foreach($modul['ekskul'] as $item)
    <tr>
        <td>{{ $item->ekskulMaster?->nama ?? '—' }}</td>
        <td>{{ $item->labelLevel() }}</td>
        <td>{{ $item->tanggal_mulai?->translatedFormat('d M Y') ?? '—' }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif
