<?php

namespace App\Filament\Widgets;

use App\Models\Santri;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UstadzProgressHafalanChart extends ChartWidget
{
    protected ?string $heading = 'Progress Hafalan Santri';

    protected ?string $description = 'Total setoran yang telah diterima per santri halaqah';

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

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

        $labels = [];
        $data   = [];

        foreach ($santriList as $santri) {
            $nama     = $santri->nama_panggilan ?: explode(' ', $santri->nama_lengkap)[0];
            $labels[] = $nama;
            $data[]   = (int) ($rows[$santri->id] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Total Setoran',
                    'data'            => $data,
                    'backgroundColor' => '#3b82f6',
                    'borderRadius'    => 6,
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
