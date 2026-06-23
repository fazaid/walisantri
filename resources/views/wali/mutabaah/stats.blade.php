@extends('wali.layouts.app')

@section('title', 'Detail Mutabaah')
@section('subtitle', $santri->nama_lengkap)
@section('back_url', route('wali.santri.show', $santri->id))

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="space-y-5">

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 gap-3">

        <div class="bg-purple-50 border border-purple-100 rounded-2xl p-4 col-span-2">
            <p class="text-xs font-medium text-purple-600 mb-1">Rata-rata Amalan</p>
            <p class="text-3xl font-bold text-purple-700 leading-tight">
                {{ $rataRata }}<span class="text-base font-medium ml-0.5">%</span>
            </p>
            <div class="mt-2 h-2 rounded-full bg-purple-100 overflow-hidden">
                <div class="h-full rounded-full {{ $rataRata >= 70 ? 'bg-green-400' : ($rataRata >= 40 ? 'bg-yellow-400' : 'bg-red-400') }}"
                     style="width: {{ $rataRata }}%"></div>
            </div>
            <p class="text-xs text-purple-400 mt-1.5">dari seluruh waktu tercatat</p>
        </div>

        <div class="bg-teal-50 border border-teal-100 rounded-2xl p-4">
            <p class="text-xs font-medium text-teal-600 mb-1">Hari Tercatat</p>
            <p class="text-3xl font-bold text-teal-700 leading-tight">{{ $totalHari }}</p>
            <p class="text-xs text-teal-500 mt-1">hari</p>
        </div>

        @php
            $amalUtama = collect($breakdownAmal)->firstWhere('tipe', 'hitungan') ?? collect($breakdownAmal)->first();
        @endphp
        @if($amalUtama)
        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4">
            <p class="text-xs font-medium text-amber-600 mb-1">{{ $amalUtama['icon'] }} {{ $amalUtama['label'] }}</p>
            <p class="text-3xl font-bold text-amber-700 leading-tight">{{ $amalUtama['pct'] }}<span class="text-base font-medium ml-0.5">%</span></p>
            <p class="text-xs text-amber-500 mt-1">{{ $amalUtama['total'] }}/{{ $amalUtama['max'] }} {{ $amalUtama['unit'] }}</p>
        </div>
        @endif

    </div>

    {{-- Trend 12 Bulan --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="font-semibold text-gray-800 mb-4">Tren Amalan 12 Bulan Terakhir</p>
        <div class="relative" style="height: 200px">
            <canvas id="chartTrend"></canvas>
        </div>
    </div>

    {{-- Breakdown per Amal --}}
    @if($totalHari > 0)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="font-semibold text-gray-800 mb-3">Konsistensi per Amal</p>
        <div class="space-y-3">
            @foreach($breakdownAmal as $aml)
            @php
                $pct = $aml['pct'];
                $barColor = $pct >= 80 ? 'bg-green-400' : ($pct >= 50 ? 'bg-yellow-400' : 'bg-red-300');
            @endphp
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-gray-600">{{ $aml['icon'] }} {{ $aml['label'] }}</span>
                    <span class="text-xs font-semibold text-gray-700">
                        {{ $aml['total'] }}/{{ $aml['max'] }} {{ $aml['unit'] }}
                        <span class="text-gray-400 font-normal">({{ $pct }}%)</span>
                    </span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full {{ $barColor }} rounded-full transition-all" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Riwayat 30 Hari Terakhir --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="font-semibold text-gray-800">Riwayat Harian</p>
            <span class="text-xs text-gray-400">30 terakhir</span>
        </div>

        @forelse($riwayat as $rec)
        @php
            $dayPct = \App\Services\MutabaahScoreCalculator::persentase($rec);
            $scoreBg = $dayPct >= 80 ? 'bg-green-100 text-green-700' : ($dayPct >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
        @endphp
        <div class="px-4 py-3 border-b border-gray-50 last:border-0">
            <div class="flex items-center justify-between gap-3">

                {{-- Tanggal + udzur --}}
                <div class="flex-shrink-0 w-20">
                    <p class="text-xs font-semibold text-gray-800">{{ $rec->tanggal->translatedFormat('d M') }}</p>
                    <p class="text-[10px] text-gray-400">{{ $rec->tanggal->translatedFormat('Y') }}</p>
                    @if($rec->status_udzur !== 'Tidak')
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-orange-100 text-orange-600 mt-0.5 inline-block">
                        {{ str_replace('_', ' ', $rec->status_udzur) }}
                    </span>
                    @endif
                </div>

                {{-- Amal icons --}}
                <div class="flex items-center gap-1 flex-1 flex-wrap">
                    @foreach($amalMasterList as $item)
                    @php
                        $nilai = $rec->amalan[$item->kode] ?? null;
                    @endphp
                    @if($item->tipe === 'hitungan')
                    <span class="text-[10px] px-1.5 py-0.5 rounded font-medium
                        {{ $nilai >= $item->nilai_maks * 0.8 ? 'bg-teal-100 text-teal-700' : ($nilai >= $item->nilai_maks * 0.4 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500') }}">
                        {{ $item->icon }}{{ $nilai ?? 0 }}/{{ $item->nilai_maks }}
                    </span>
                    @else
                    <span class="text-[10px] w-6 h-6 flex items-center justify-center rounded
                        {{ $nilai ? 'bg-green-100' : 'bg-gray-100 opacity-40' }}">
                        {{ $item->icon }}
                    </span>
                    @endif
                    @endforeach
                </div>

                {{-- Skor harian --}}
                <span class="text-xs font-bold px-2 py-1 rounded-lg flex-shrink-0 {{ $scoreBg }}">
                    {{ $dayPct }}%
                </span>

            </div>
        </div>
        @empty
        <div class="px-4 py-6 text-center text-sm text-gray-400">Belum ada data mutabaah.</div>
        @endforelse
    </div>

</div>

<script>
new Chart(document.getElementById('chartTrend').getContext('2d'), {
    type: 'line',
    data: {
        labels: @json($bulanLabels),
        datasets: [
            {
                label: 'Rata-rata Amalan (%)',
                data: @json($dataAvgPct),
                borderColor: 'rgba(139, 92, 246, 0.9)',
                backgroundColor: 'rgba(139, 92, 246, 0.08)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: 'rgba(139, 92, 246, 0.9)',
                pointRadius: 4,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.parsed.y}%`,
                },
            },
        },
        scales: {
            x: { ticks: { font: { size: 10 } } },
            y: {
                beginAtZero: true,
                max: 100,
                ticks: { font: { size: 11 }, callback: v => v + '%' },
                grid: { color: 'rgba(0,0,0,0.04)' },
            },
        },
    },
});
</script>
@endsection
