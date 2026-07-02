<?php

namespace App\Filament\Widgets;

use App\Models\KesantrianMutabaah;
use App\Models\Santri;
use App\Services\MutabaahScoreCalculator;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class UstadzAmalanChart extends ChartWidget
{
    protected ?string $heading = 'Amalan per Santri — 7 Hari';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = ['default' => 2, 'md' => 1];

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'ustadz';
    }

    public function getDescription(): string|Htmlable|null
    {
        $labels = $this->getCachedData()['labels'] ?? [];
        return empty($labels)
            ? 'Belum ada data mutabaah yang diinput dalam 7 hari terakhir.'
            : 'Persentase amalan tiap santri halaqah Anda';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $ustadzId    = Auth::id();
        $pesantrenId = Auth::user()?->pesantren_id;

        $santriList = Santri::where('pesantren_id', $pesantrenId)
            ->where('pembimbing_ustadz_id', $ustadzId)
            ->where('status_aktif', true)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nama_panggilan']);

        if ($santriList->isEmpty()) {
            return ['datasets' => [['label' => '', 'data' => []]], 'labels' => []];
        }

        $start = now()->subDays(6)->toDateString();
        $end   = now()->toDateString();

        $allMutabaah = KesantrianMutabaah::whereIn('santri_id', $santriList->pluck('id'))
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->groupBy('santri_id');

        if ($allMutabaah->isEmpty()) {
            return ['datasets' => [['label' => '', 'data' => []]], 'labels' => []];
        }

        $labels = [];
        $data   = [];
        $colors = [];

        foreach ($santriList as $santri) {
            $list = $allMutabaah->get($santri->id, collect());
            $pct  = MutabaahScoreCalculator::persentaseRataRata($list);

            // Nama pendek untuk label chart
            $nama     = $santri->nama_panggilan
                ?: explode(' ', $santri->nama_lengkap)[0];

            $labels[] = $nama;
            $data[]   = $pct;
            $colors[] = $pct >= 75 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
        }

        return [
            'datasets' => [
                [
                    'label'           => '% Amalan',
                    'data'            => $data,
                    'backgroundColor' => $colors,
                    'borderRadius'    => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array|\Filament\Support\RawJs|null
    {
        return \Filament\Support\RawJs::make("{
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    ticks: { callback: (v) => v + '%' }
                }
            },
            plugins: { legend: { display: false } }
        }");
    }
}
