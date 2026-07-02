<?php

namespace App\Filament\Widgets;

use App\Models\Santri;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UstadzTrendSetoranChart extends ChartWidget
{
    protected ?string $heading = 'Tren Setoran 7 Hari Terakhir';

    protected ?string $description = 'Jumlah setoran hafalan santri per hari';

    protected static ?int $sort = 15;

    protected int|string|array $columnSpan = ['default' => 2, 'md' => 1];

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'ustadz';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $ustadzId    = Auth::id();
        $pesantrenId = Auth::user()?->pesantren_id;

        $santriIds = Santri::where('pesantren_id', $pesantrenId)
            ->where('pembimbing_ustadz_id', $ustadzId)
            ->where('status_aktif', true)
            ->pluck('id');

        if ($santriIds->isEmpty()) {
            return ['datasets' => [['label' => '', 'data' => []]], 'labels' => []];
        }

        $start = now()->subDays(6)->toDateString();

        $rows = DB::table('tahfidz_progress')
            ->whereIn('santri_id', $santriIds)
            ->where('pesantren_id', $pesantrenId)
            ->whereBetween('tanggal', [$start, now()->toDateString()])
            ->selectRaw('tanggal, COUNT(*) as count')
            ->groupBy('tanggal')
            ->pluck('count', 'tanggal');

        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $day      = now()->subDays($i);
            $labels[] = $day->format('d/m');
            $data[]   = (int) ($rows[$day->toDateString()] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'            => 'Jumlah Setoran',
                    'data'             => $data,
                    'borderColor'      => '#14b8a6',
                    'backgroundColor'  => '#14b8a622',
                    'fill'             => true,
                    'tension'          => 0.4,
                    'pointRadius'      => 3,
                    'pointHoverRadius' => 5,
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
                    'ticks'       => ['stepSize' => 1],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
