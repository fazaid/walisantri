<?php

namespace App\Filament\Widgets;

use App\Models\KesantrianMutabaah;
use App\Models\Santri;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class UstadzKonsistensiMutabaahChart extends ChartWidget
{
    protected ?string $heading = 'Konsistensi Input Mutaba\'ah (30 Hari)';

    protected ?string $description = 'Persentase hari input amalan dari total 30 hari terakhir';

    protected static ?int $sort = 25;

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
        $ustadzId = Auth::id();

        $santriList = Santri::where('pembimbing_ustadz_id', $ustadzId)
            ->where('status_aktif', true)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nama_panggilan']);

        if ($santriList->isEmpty()) {
            return ['datasets' => [['label' => '', 'data' => []]], 'labels' => []];
        }

        $start = now()->subDays(29)->toDateString();

        $rows = KesantrianMutabaah::whereIn('santri_id', $santriList->pluck('id'))
            ->where('tanggal', '>=', $start)
            ->selectRaw('santri_id, COUNT(DISTINCT tanggal) as hari_input')
            ->groupBy('santri_id')
            ->pluck('hari_input', 'santri_id');

        $labels = [];
        $data   = [];
        $colors = [];

        foreach ($santriList as $santri) {
            $nama       = $santri->nama_panggilan ?: explode(' ', $santri->nama_lengkap)[0];
            $hariInput  = (int) ($rows[$santri->id] ?? 0);
            $pct        = round(($hariInput / 30) * 100);

            $labels[] = $nama;
            $data[]   = $pct;
            $colors[] = $pct >= 80 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
        }

        return [
            'datasets' => [
                [
                    'label'           => '% Konsistensi',
                    'data'            => $data,
                    'backgroundColor' => $colors,
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
                    'min'   => 0,
                    'max'   => 100,
                    'ticks' => ['callback' => RawJs::make("(v) => v + '%'")],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
