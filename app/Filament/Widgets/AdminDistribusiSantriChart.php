<?php

namespace App\Filament\Widgets;

use App\Models\Kelas;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AdminDistribusiSantriChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Santri per Kelas';

    protected ?string $description = 'Jumlah santri aktif di setiap kelas';

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $pesantrenId = Auth::user()?->pesantren_id;

        $kelas = Kelas::where('pesantren_id', $pesantrenId)
            ->withCount(['santri' => fn ($q) => $q->where('status_aktif', true)])
            ->orderBy('nama_kelas')
            ->get();

        if ($kelas->isEmpty()) {
            return ['datasets' => [['data' => []]], 'labels' => []];
        }

        $palette = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316', '#84cc16', '#a855f7'];
        $colors  = array_slice($palette, 0, $kelas->count());

        return [
            'datasets' => [
                [
                    'data'            => $kelas->pluck('santri_count')->toArray(),
                    'backgroundColor' => $colors,
                    'hoverOffset'     => 6,
                ],
            ],
            'labels' => $kelas->pluck('nama_kelas')->toArray(),
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'right'],
            ],
            'cutout' => '60%',
        ];
    }
}
