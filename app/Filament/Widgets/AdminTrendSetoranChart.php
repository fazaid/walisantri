<?php

namespace App\Filament\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminTrendSetoranChart extends ChartWidget
{
    protected ?string $heading = 'Tren Setoran 7 Hari Terakhir';

    protected static ?int $sort = 9;

    // span 1, bukan ['default'=>2,'md'=>1] — lihat AdminSppStatusChart untuk alasannya.
    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public function getDescription(): string|Htmlable|null
    {
        $data = $this->getCachedData()['datasets'][0]['data'] ?? [];

        return array_sum($data) === 0
            ? 'Belum ada setoran hafalan 7 hari terakhir.'
            : 'Jumlah setoran hafalan seluruh santri per hari';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $pesantrenId = Auth::user()?->pesantren_id;
        $start       = now()->subDays(6)->toDateString();

        $rows = DB::table('tahfidz_progress')
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
