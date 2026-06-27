<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminSppStatusChart extends ChartWidget
{
    protected ?string $heading = 'Status SPP Bulan Ini';

    protected ?string $description = 'Distribusi tagihan SPP bulan berjalan';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

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

        $rows = DB::table('tagihan_spp')
            ->where('pesantren_id', $pesantrenId)
            ->where('bulan', now()->month)
            ->where('tahun', now()->year)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $belum     = (int) ($rows['belum_bayar'] ?? 0);
        $menunggu  = (int) ($rows['menunggu_konfirmasi'] ?? 0);
        $lunas     = (int) ($rows['lunas'] ?? 0);
        $total     = $belum + $menunggu + $lunas;

        return [
            'datasets' => [
                [
                    'data'            => [$belum, $menunggu, $lunas],
                    'backgroundColor' => ['#ef4444', '#f59e0b', '#10b981'],
                    'hoverOffset'     => 6,
                ],
            ],
            'labels' => ['Belum Bayar', 'Menunggu Konfirmasi', 'Lunas'],
        ];
    }

    protected function getOptions(): array|\Filament\Support\RawJs|null
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '65%',
        ];
    }
}
