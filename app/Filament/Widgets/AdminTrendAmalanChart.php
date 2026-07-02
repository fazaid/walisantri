<?php

namespace App\Filament\Widgets;

use App\Models\KesantrianMutabaah;
use App\Models\Santri;
use App\Services\MutabaahScoreCalculator;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class AdminTrendAmalanChart extends ChartWidget
{
    protected ?string $heading = 'Tren Amalan 7 Hari Terakhir';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public function getDescription(): string|Htmlable|null
    {
        $data = $this->getCachedData()['datasets'][0]['data'] ?? [];

        return array_sum($data) === 0
            ? 'Belum ada data mutaba\'ah 7 hari terakhir.'
            : 'Rata-rata persentase amalan seluruh santri';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $pesantrenId = Auth::user()?->pesantren_id;
        $santriIds   = Santri::where('pesantren_id', $pesantrenId)
            ->where('status_aktif', true)
            ->pluck('id');

        $start = now()->subDays(6)->startOfDay();

        // 1 query untuk semua record 7 hari
        $allMutabaah = KesantrianMutabaah::whereIn('santri_id', $santriIds)
            ->whereBetween('tanggal', [$start->toDateString(), now()->toDateString()])
            ->get()
            ->groupBy(fn ($m) => $m->tanggal->toDateString());

        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = now()->subDays($i);
            $key      = $date->toDateString();
            $list     = $allMutabaah->get($key, collect());
            $pct      = MutabaahScoreCalculator::persentaseRataRata($list);

            $labels[] = $date->translatedFormat('D d/m');
            $data[]   = $pct;
        }

        $avg   = count($data) > 0 ? array_sum($data) / count($data) : 0;
        $color = $avg >= 75 ? '#10b981' : ($avg >= 50 ? '#f59e0b' : '#ef4444');

        return [
            'datasets' => [
                [
                    'label'           => '% Amalan',
                    'data'            => $data,
                    'borderColor'     => $color,
                    'backgroundColor' => $color . '22',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 4,
                    'pointHoverRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array|\Filament\Support\RawJs|null
    {
        return [
            'scales' => [
                'y' => [
                    'min'   => 0,
                    'max'   => 100,
                    'ticks' => ['callback' => \Filament\Support\RawJs::make("(v) => v + '%'")],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
