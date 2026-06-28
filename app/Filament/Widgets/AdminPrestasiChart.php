<?php

namespace App\Filament\Widgets;

use App\Models\PrestasiSantri;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AdminPrestasiChart extends ChartWidget
{
    protected ?string $heading = 'Prestasi Santri per Kategori';

    protected ?string $description = 'Jumlah pencapaian santri berdasarkan bidang';

    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = PrestasiSantri::selectRaw('kategori, COUNT(*) as count')
            ->groupBy('kategori')
            ->pluck('count', 'kategori');

        $kategoriList = array_keys(PrestasiSantri::$kategoriOptions);
        $palette      = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316', '#6b7280'];

        $labels = [];
        $data   = [];
        $colors = [];

        foreach ($kategoriList as $i => $kategori) {
            $labels[] = $kategori;
            $data[]   = (int) ($rows[$kategori] ?? 0);
            $colors[] = $palette[$i % count($palette)];
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Jumlah Prestasi',
                    'data'            => $data,
                    'backgroundColor' => $colors,
                    'borderRadius'    => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'indexAxis' => 'y',
            'scales'    => [
                'x' => [
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
