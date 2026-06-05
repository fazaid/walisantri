{{-- File: resources/views/wali/kesehatan/stats.blade.php --}}
@extends('wali.layouts.app')

@section('title', 'Statistik Kesehatan')
@section('subtitle', $santri->nama_lengkap)
@section('back_url', route('wali.santri.show', $santri->id))

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="space-y-5">

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 gap-3">

        {{-- Status Terkini --}}
        @php
            $statusTerkiniConfig = match($statusTerkini?->status_pemulihan) {
                'Rawat_Mandiri'   => ['bg-yellow-50', 'border-yellow-100', 'text-yellow-700', 'Rawat Mandiri'],
                'Istirahat_Total' => ['bg-red-50',    'border-red-100',    'text-red-700',    'Istirahat Total'],
                'Rujukan_Luar'    => ['bg-red-50',    'border-red-100',    'text-red-800',    'Rujukan Luar'],
                default           => ['bg-green-50',  'border-green-100',  'text-green-700',  'Sehat'],
            };
            [$sBg, $sBorder, $sText, $sLabel] = $statusTerkiniConfig;
        @endphp
        <div class="{{ $sBg }} border {{ $sBorder }} rounded-2xl p-4">
            <p class="text-xs font-medium {{ $sText }} mb-1">Status Terkini</p>
            <p class="text-lg font-bold {{ $sText }} leading-tight">{{ $sLabel }}</p>
            @if($statusTerkini)
            <p class="text-xs {{ $sText }} opacity-70 mt-1">
                {{ $statusTerkini->tanggal_periksa->translatedFormat('d M Y') }}
            </p>
            @endif
        </div>

        {{-- Total Pemeriksaan --}}
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4">
            <p class="text-xs font-medium text-blue-600 mb-1">Total Periksa</p>
            <p class="text-3xl font-bold text-blue-700 leading-tight">{{ $totalPemeriksaan }}</p>
            <p class="text-xs text-blue-500 mt-1">kali tercatat</p>
        </div>

        {{-- BB & TB terkini --}}
        @if($statusTerkini?->berat_badan)
        <div class="bg-purple-50 border border-purple-100 rounded-2xl p-4">
            <p class="text-xs font-medium text-purple-600 mb-1">Berat Badan</p>
            <p class="text-2xl font-bold text-purple-700 leading-tight">
                {{ $statusTerkini->berat_badan }}<span class="text-sm ml-1">kg</span>
            </p>
        </div>
        @endif

        @if($statusTerkini?->tinggi_badan)
        <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4">
            <p class="text-xs font-medium text-indigo-600 mb-1">Tinggi Badan</p>
            <p class="text-2xl font-bold text-indigo-700 leading-tight">
                {{ $statusTerkini->tinggi_badan }}<span class="text-sm ml-1">cm</span>
            </p>
        </div>
        @endif
    </div>

    {{-- Grafik Pemeriksaan per Bulan --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="font-semibold text-gray-800 mb-4">Frekuensi Pemeriksaan 12 Bulan Terakhir</p>
        <div class="relative" style="height: 180px">
            <canvas id="chartPemeriksaan"></canvas>
        </div>
    </div>

    {{-- Distribusi Kategori Keluhan --}}
    @if($distribusiKeluhan->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="font-semibold text-gray-800 mb-3">Jenis Keluhan</p>
        @php
            $totalKeluhan = $distribusiKeluhan->sum();
            $keluhanLabel = [
                'Demam'       => 'Demam',
                'Batuk_Pilek' => 'Batuk/Pilek',
                'Sakit_Perut' => 'Sakit Perut',
                'Pusing'      => 'Pusing',
                'Kulit_Gatal' => 'Kulit Gatal',
                'Luka_Fisik'  => 'Luka Fisik',
                'Lainnya'     => 'Lainnya',
            ];
        @endphp
        <div class="space-y-2">
            @foreach($distribusiKeluhan as $key => $count)
            @php $pct = $totalKeluhan > 0 ? round($count / $totalKeluhan * 100) : 0; @endphp
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-600 w-24 flex-shrink-0">{{ $keluhanLabel[$key] ?? $key }}</span>
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-rose-400 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
                <span class="text-xs text-gray-500 w-8 text-right">{{ $count }}x</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Distribusi Status Pemulihan --}}
    @if($distribusiStatus->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="font-semibold text-gray-800 mb-3">Status Pemulihan</p>
        @php
            $totalStatus = $distribusiStatus->sum();
            $statusConfig = [
                'Rawat_Mandiri'   => ['text-yellow-700', 'bg-yellow-400', 'Rawat Mandiri'],
                'Istirahat_Total' => ['text-orange-700', 'bg-orange-400', 'Istirahat Total'],
                'Rujukan_Luar'    => ['text-red-700',    'bg-red-400',    'Rujukan Luar'],
            ];
        @endphp
        <div class="space-y-2">
            @foreach($statusConfig as $key => [$textClass, $barClass, $label])
            @php $count = $distribusiStatus[$key] ?? 0; $pct = $totalStatus > 0 ? round($count / $totalStatus * 100) : 0; @endphp
            <div class="flex items-center gap-3">
                <span class="text-xs {{ $textClass }} w-28 flex-shrink-0">{{ $label }}</span>
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full {{ $barClass }} rounded-full" style="width: {{ $pct }}%"></div>
                </div>
                <span class="text-xs text-gray-500 w-8 text-right">{{ $count }}x</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Tren BB/TB --}}
    @if($trenFisik->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="font-semibold text-gray-800 mb-4">Tren Berat & Tinggi Badan</p>
        <div class="relative" style="height: 180px">
            <canvas id="chartFisik"></canvas>
        </div>
    </div>
    @endif

    {{-- Riwayat Terbaru --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="font-semibold text-gray-800">Riwayat Pemeriksaan</p>
            <span class="text-xs text-gray-400">10 terakhir</span>
        </div>
        @forelse($riwayat as $item)
        <div class="px-4 py-3 border-b border-gray-50 last:border-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">
                        {{ str_replace('_', '/', $item->kategori_keluhan) }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ $item->tanggal_periksa->translatedFormat('d M Y') }}
                        @if($item->berat_badan) · {{ $item->berat_badan }} kg @endif
                    </p>
                    @if($item->tindakan_dan_obat)
                    <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $item->tindakan_dan_obat }}</p>
                    @endif
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0
                    {{ $item->status_pemulihan === 'Rawat_Mandiri'   ? 'bg-yellow-100 text-yellow-700' :
                       ($item->status_pemulihan === 'Istirahat_Total' ? 'bg-orange-100 text-orange-700'
                                                                      : 'bg-red-100 text-red-700') }}">
                    {{ str_replace('_', ' ', $item->status_pemulihan) }}
                </span>
            </div>
        </div>
        @empty
        <div class="px-4 py-6 text-center text-sm text-gray-400">Belum ada riwayat pemeriksaan.</div>
        @endforelse
    </div>

</div>

<script>
// Grafik frekuensi pemeriksaan
new Chart(document.getElementById('chartPemeriksaan').getContext('2d'), {
    type: 'bar',
    data: {
        labels: @json($bulanLabels),
        datasets: [{
            label: 'Pemeriksaan',
            data: @json($dataPemeriksaan),
            backgroundColor: 'rgba(244, 63, 94, 0.7)',
            borderRadius: 4,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { font: { size: 10 } } },
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } },
        },
    },
});

@if($trenFisik->isNotEmpty())
// Grafik tren BB/TB
new Chart(document.getElementById('chartFisik').getContext('2d'), {
    type: 'line',
    data: {
        labels: @json($trenFisik->pluck('tanggal_periksa')->map(fn($d) => $d->translatedFormat('d M Y'))),
        datasets: [
            {
                label: 'Berat (kg)',
                data: @json($trenFisik->pluck('berat_badan')),
                borderColor: 'rgba(139, 92, 246, 0.9)',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                tension: 0.3,
                yAxisID: 'yBB',
            },
            {
                label: 'Tinggi (cm)',
                data: @json($trenFisik->pluck('tinggi_badan')),
                borderColor: 'rgba(99, 102, 241, 0.9)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.3,
                yAxisID: 'yTB',
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: {
            x: { ticks: { font: { size: 10 } } },
            yBB: { position: 'left',  title: { display: true, text: 'kg', font: { size: 10 } } },
            yTB: { position: 'right', title: { display: true, text: 'cm', font: { size: 10 } }, grid: { drawOnChartArea: false } },
        },
    },
});
@endif
</script>
@endsection
