{{-- File: resources/views/wali/santri/show.blade.php --}}
@extends('wali.layouts.app')

@section('title', $santri->nama_lengkap)
@section('subtitle', $santri->kelas . ' · ' . $santri->kamar)
@section('back_url', route('wali.dashboard'))

@section('content')
<div class="space-y-5">

    {{-- ═══════════════════════════════════════════════════════════════════════
         SUMMARY CARDS  2×2 grid
    ════════════════════════════════════════════════════════════════════════ --}}
    @php
        /* ── Card 3: warna berdasarkan status pemulihan ── */
        $kesehatanStatus = $statusKesehatanTerkini['status_pemulihan'] ?? null;
        [$kBg, $kBorder, $kText, $kLabel] = match ($kesehatanStatus) {
            'Rawat_Mandiri'   => ['bg-green-50',  'border-green-200',  'text-green-700',  'Rawat Mandiri'],
            'Istirahat_Total' => ['bg-yellow-50', 'border-yellow-200', 'text-yellow-700', 'Istirahat Total'],
            'Rujukan_Luar'    => ['bg-red-50',    'border-red-200',    'text-red-700',    'Rujukan Luar'],
            default           => ['bg-green-50',  'border-green-200',  'text-green-700',  'Sehat'],
        };

        /* ── Card 2: warna progress bar amalan ── */
        $progressColor = $persentaseAmalanMingguIni >= 70
            ? 'bg-green-500'
            : ($persentaseAmalanMingguIni >= 40 ? 'bg-yellow-400' : 'bg-red-400');

        /* ── Card 4: format periode rapor singkat ── */
        if ($raporTahfidzTerakhir) {
            $periodeLabel = match ($raporTahfidzTerakhir['periode']) {
                'Semester_Ganjil' => 'Sem. Ganjil',
                'Semester_Genap'  => 'Sem. Genap',
                default           => $raporTahfidzTerakhir['periode'],
            };
            $tahunSingkat = implode('/', array_map(
                fn ($y) => substr(trim($y), 2),
                explode('/', $raporTahfidzTerakhir['tahun_ajaran'])
            ));
        }
    @endphp

    <div class="grid grid-cols-2 gap-3">

        {{-- Card 1 — Estimasi Hafalan --}}
        <div class="bg-teal-50 border border-teal-200 rounded-2xl p-4">
            <div class="flex items-center gap-1.5 mb-2">
                <span class="text-lg leading-none">📖</span>
                <span class="text-xs font-medium text-teal-600">Estimasi Hafalan</span>
            </div>
            @if($totalJuzHafalan > 0)
                <p class="text-2xl font-bold text-teal-700 leading-tight">
                    {{ $totalJuzHafalan }}<span class="text-sm font-medium ml-1">Juz</span>
                </p>
            @else
                <p class="text-sm font-medium text-teal-400">Belum ada data</p>
            @endif
        </div>

        {{-- Card 2 — Amalan Minggu Ini --}}
        <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4">
            <div class="flex items-center gap-1.5 mb-2">
                <span class="text-lg leading-none">✨</span>
                <span class="text-xs font-medium text-purple-600">Amalan Minggu Ini</span>
            </div>
            <p class="text-2xl font-bold text-purple-700 leading-tight">
                {{ $persentaseAmalanMingguIni }}<span class="text-sm font-medium ml-0.5">%</span>
            </p>
            {{-- Progress bar tipis --}}
            <div class="mt-2 h-1.5 rounded-full bg-gray-200 overflow-hidden">
                <div class="h-1.5 rounded-full {{ $progressColor }}"
                     style="width: {{ $persentaseAmalanMingguIni }}%"></div>
            </div>
        </div>

        {{-- Card 3 — Status Kesehatan --}}
        <div class="{{ $kBg }} border {{ $kBorder }} rounded-2xl p-4">
            <div class="flex items-center gap-1.5 mb-2">
                <span class="text-lg leading-none">🏥</span>
                <span class="text-xs font-medium {{ $kText }}">Status Kesehatan</span>
            </div>
            <p class="text-sm font-bold {{ $kText }} leading-tight">{{ $kLabel }}</p>
            @if($statusKesehatanTerkini)
                <p class="text-xs {{ $kText }} opacity-70 mt-0.5">
                    {{ $statusKesehatanTerkini['tanggal_periksa']->translatedFormat('d M Y') }}
                </p>
            @endif
        </div>

        {{-- Card 4 — Rapor Tahfidz --}}
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
            <div class="flex items-center gap-1.5 mb-2">
                <span class="text-lg leading-none">⭐</span>
                <span class="text-xs font-medium text-amber-600">Rapor Terakhir</span>
            </div>
            @if($raporTahfidzTerakhir)
                <p class="text-2xl font-bold text-amber-700 leading-tight">
                    {{ $raporTahfidzTerakhir['nilai_hafalan'] }}
                </p>
                <p class="text-xs text-amber-600 mt-0.5">
                    {{ $periodeLabel }} {{ $tahunSingkat }}
                </p>
            @else
                <p class="text-sm font-medium text-amber-400">Belum ada rapor</p>
            @endif
        </div>

    </div>{{-- /grid --}}

    {{-- Info Card Santri --}}
    <div class="bg-teal-700 text-white rounded-2xl p-4 shadow">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-teal-500 flex items-center justify-center flex-shrink-0">
                <span class="text-2xl font-bold">
                    {{ strtoupper(substr($santri->nama_lengkap, 0, 1)) }}
                </span>
            </div>
            <div>
                <p class="font-bold text-lg leading-tight">{{ $santri->nama_lengkap }}</p>
                <p class="text-teal-200 text-sm">NIS: {{ $santri->nis }}</p>
                <p class="text-teal-200 text-sm">Pembimbing: {{ $santri->pembimbing->name }}</p>
            </div>
        </div>
    </div>

    {{-- Riwayat Setoran Tahfidz --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">📖 Setoran Tahfidz</h2>
            <span class="text-xs text-gray-400">10 terbaru</span>
        </div>

        @forelse($tahfidzRecent as $progress)
        <div class="px-4 py-3 border-b border-gray-50 last:border-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 text-sm">{{ $progress->nama_surah }}</p>
                    <p class="text-xs text-gray-500">
                        Ayat {{ $progress->ayat_mulai }}–{{ $progress->ayat_selesai }}
                        · {{ $progress->tanggal->translatedFormat('d M Y') }}
                    </p>
                    @if($progress->catatan_evaluasi)
                    <p class="text-xs text-gray-400 mt-1 italic">{{ $progress->catatan_evaluasi }}</p>
                    @endif
                </div>
                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $progress->tipe_setoran === 'Sabaq' ? 'bg-green-100 text-green-700' :
                           ($progress->tipe_setoran === 'Sabqi' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }}">
                        {{ $progress->tipe_setoran }}
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $progress->nilai_kelancaran === 'Mumtaz' ? 'bg-emerald-100 text-emerald-700' :
                           ($progress->nilai_kelancaran === 'Jayyid Jiddan' ? 'bg-sky-100 text-sky-700' :
                           ($progress->nilai_kelancaran === 'Jayyid' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                        {{ $progress->nilai_kelancaran }}
                    </span>
                </div>
            </div>
        </div>
        @empty
        <div class="px-4 py-6 text-center text-sm text-gray-400">
            Belum ada data setoran tahfidz.
        </div>
        @endforelse
    </div>

    {{-- Riwayat Kesehatan --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">🏥 Riwayat Kesehatan</h2>
            <span class="text-xs text-gray-400">5 terbaru</span>
        </div>

        @forelse($kesehatanRecent as $kesehatan)
        <div class="px-4 py-3 border-b border-gray-50 last:border-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 text-sm">
                        {{ str_replace('_', ' ', $kesehatan->kategori_keluhan) }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ $kesehatan->tanggal_periksa->translatedFormat('d M Y') }}
                    </p>
                    <p class="text-xs text-gray-600 mt-1">{{ $kesehatan->tindakan_dan_obat }}</p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0
                    {{ $kesehatan->status_pemulihan === 'Rawat_Mandiri' ? 'bg-green-100 text-green-700' :
                       ($kesehatan->status_pemulihan === 'Istirahat_Total' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                    {{ str_replace('_', ' ', $kesehatan->status_pemulihan) }}
                </span>
            </div>
        </div>
        @empty
        <div class="px-4 py-6 text-center text-sm text-gray-400">
            Tidak ada riwayat kesehatan tercatat.
        </div>
        @endforelse
    </div>

</div>
@endsection