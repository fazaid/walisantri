{{-- File: resources/views/wali/tahfidz/stats.blade.php --}}
@extends('wali.layouts.app')

@section('title', 'Statistik Tahfidz')
@section('subtitle', $santri->nama_lengkap)
@section('back_url', route('wali.santri.show', $santri->id))

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="space-y-5">

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-teal-50 border border-teal-100 rounded-2xl p-4 col-span-2">
            <p class="text-xs text-teal-600 font-medium mb-1">Estimasi Hafalan</p>
            <p class="text-3xl font-bold text-teal-700 leading-tight">
                {{ $estimasiJuz }}<span class="text-base font-medium ml-1">juz</span>
            </p>
            <p class="text-xs text-teal-500 mt-1">dari total ayat yang disetorkan</p>
        </div>

        <div class="bg-green-50 border border-green-100 rounded-2xl p-4">
            <p class="text-xs text-green-600 font-medium">Sabaq</p>
            <p class="text-2xl font-bold text-green-700">{{ $totalSabaq }}</p>
            <p class="text-xs text-green-500">hafalan baru</p>
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4">
            <p class="text-xs text-blue-600 font-medium">Sabqi</p>
            <p class="text-2xl font-bold text-blue-700">{{ $totalSabqi }}</p>
            <p class="text-xs text-blue-500">hafalan kemarin</p>
        </div>

        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 col-span-2">
            <p class="text-xs text-amber-600 font-medium">Manzil</p>
            <p class="text-2xl font-bold text-amber-700">{{ $totalManzil }}</p>
            <p class="text-xs text-amber-500">hafalan lama</p>
        </div>
    </div>

    {{-- Grafik Setoran per Bulan --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="font-semibold text-gray-800 mb-4">Setoran 12 Bulan Terakhir</p>
        <div class="relative" style="height: 220px">
            <canvas id="chartSetoran"></canvas>
        </div>
    </div>

    {{-- Distribusi Nilai Kelancaran --}}
    @if($distribusiNilai->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="font-semibold text-gray-800 mb-3">Distribusi Nilai Kelancaran</p>
        @php
            $nilaiConfig = [
                'Mumtaz'        => ['bg-emerald-100', 'text-emerald-700'],
                'Jayyid Jiddan' => ['bg-sky-100',     'text-sky-700'],
                'Jayyid'        => ['bg-yellow-100',  'text-yellow-700'],
                'Maqbul'        => ['bg-red-100',     'text-red-700'],
            ];
            $totalNilai = $distribusiNilai->sum();
        @endphp
        <div class="space-y-2">
            @foreach($nilaiConfig as $label => [$bg, $text])
            @php $count = $distribusiNilai[$label] ?? 0; $pct = $totalNilai > 0 ? round($count / $totalNilai * 100) : 0; @endphp
            <div class="flex items-center gap-3">
                <span class="text-xs font-medium w-28 flex-shrink-0 {{ $text }}">{{ $label }}</span>
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full {{ $bg }} border {{ str_replace('bg-', 'border-', $bg) }}"
                         style="width: {{ $pct }}%; background-color: currentColor"></div>
                </div>
                <span class="text-xs text-gray-500 w-8 text-right">{{ $count }}x</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Riwayat Setoran Terbaru --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="font-semibold text-gray-800">Setoran Terbaru</p>
            <span class="text-xs text-gray-400">10 terakhir</span>
        </div>
        @forelse($setoranTerbaru as $s)
        <div class="px-4 py-3 border-b border-gray-50 last:border-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $s->nama_surah }}</p>
                    <p class="text-xs text-gray-500">
                        Ayat {{ $s->ayat_mulai }}–{{ $s->ayat_selesai }}
                        · {{ $s->tanggal->translatedFormat('d M Y') }}
                    </p>
                </div>
                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $s->tipe_setoran === 'Sabaq' ? 'bg-green-100 text-green-700' :
                           ($s->tipe_setoran === 'Sabqi' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }}">
                        {{ $s->tipe_setoran }}
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $s->nilai_kelancaran === 'Mumtaz' ? 'bg-emerald-100 text-emerald-700' :
                           ($s->nilai_kelancaran === 'Jayyid Jiddan' ? 'bg-sky-100 text-sky-700' :
                           ($s->nilai_kelancaran === 'Jayyid' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                        {{ $s->nilai_kelancaran }}
                    </span>
                </div>
            </div>
        </div>
        @empty
        <div class="px-4 py-6 text-center text-sm text-gray-400">Belum ada data setoran.</div>
        @endforelse
    </div>

</div>

<script>
const ctx = document.getElementById('chartSetoran').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: @json($bulanLabels),
        datasets: [
            {
                label: 'Sabaq',
                data: @json($dataSabaq),
                backgroundColor: 'rgba(20, 184, 166, 0.8)',
                borderRadius: 4,
            },
            {
                label: 'Sabqi',
                data: @json($dataSabqi),
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderRadius: 4,
            },
            {
                label: 'Manzil',
                data: @json($dataManzil),
                backgroundColor: 'rgba(251, 191, 36, 0.8)',
                borderRadius: 4,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
        },
        scales: {
            x: { stacked: true, ticks: { font: { size: 10 } } },
            y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } },
        },
    },
});
</script>
@endsection
