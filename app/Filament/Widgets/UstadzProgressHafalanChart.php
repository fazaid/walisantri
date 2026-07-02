<?php

namespace App\Filament\Widgets;

use App\Models\Santri;
use App\Services\TahfidzJuzCalculator;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UstadzProgressHafalanChart extends ChartWidget
{
    protected ?string $heading = 'Progress Hafalan Santri';

    protected ?string $description = 'Total setoran & estimasi juz hafalan per santri halaqah';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'ustadz';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $ustadzId    = Auth::id();
        $pesantrenId = Auth::user()?->pesantren_id;

        $santriList = Santri::where('pembimbing_ustadz_id', $ustadzId)
            ->where('status_aktif', true)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nama_panggilan']);

        if ($santriList->isEmpty()) {
            return ['datasets' => [['label' => '', 'data' => []]], 'labels' => []];
        }

        $rows = DB::table('tahfidz_progress')
            ->whereIn('santri_id', $santriList->pluck('id'))
            ->where('pesantren_id', $pesantrenId)
            ->selectRaw('santri_id, COUNT(*) as total_setoran')
            ->groupBy('santri_id')
            ->pluck('total_setoran', 'santri_id');

        $labels      = [];
        $dataSetoran = [];
        $dataJuz     = [];

        foreach ($santriList as $santri) {
            $progress      = TahfidzJuzCalculator::calculate($santri->id);
            $nama          = $santri->nama_panggilan ?: explode(' ', $santri->nama_lengkap)[0];
            $labels[]      = $nama;
            $dataSetoran[] = (int) ($rows[$santri->id] ?? 0);
            $dataJuz[]     = $progress['juz_hafal'];
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Total Setoran',
                    'data'            => $dataSetoran,
                    'backgroundColor' => '#3b82f6',
                    'borderRadius'    => 6,
                    'yAxisID'         => 'y',
                ],
                [
                    'label'           => 'Estimasi Juz Hafalan',
                    'data'            => $dataJuz,
                    'backgroundColor' => '#10b981',
                    'borderRadius'    => 6,
                    'yAxisID'         => 'y2',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position'    => 'left',
                    'ticks'       => ['stepSize' => 1],
                ],
                'y2' => [
                    'beginAtZero' => true,
                    'max'         => 30,
                    'position'    => 'right',
                    'grid'        => ['drawOnChartArea' => false],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => true],
            ],
        ];
    }
}
