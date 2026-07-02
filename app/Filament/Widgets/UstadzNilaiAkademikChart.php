<?php

namespace App\Filament\Widgets;

use App\Models\NilaiAkademik;
use App\Models\Santri;
use App\Services\TahunAjaranOptions;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class UstadzNilaiAkademikChart extends ChartWidget
{
    protected ?string $heading = 'Rata-rata Nilai Akademik Santri';

    protected ?string $description = 'Nilai rata-rata seluruh mata pelajaran per santri tahun ajaran ini';

    protected static ?int $sort = 30;

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

        $santriList = Santri::where('pesantren_id', $pesantrenId)
            ->where('pembimbing_ustadz_id', $ustadzId)
            ->where('status_aktif', true)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nama_panggilan']);

        if ($santriList->isEmpty()) {
            return ['datasets' => [['label' => '', 'data' => []]], 'labels' => []];
        }

        $tahunAjaran = TahunAjaranOptions::current();

        $rows = NilaiAkademik::whereIn('santri_id', $santriList->pluck('id'))
            ->where('tahun_ajaran', $tahunAjaran)
            ->selectRaw('santri_id, ROUND(AVG(nilai), 1) as avg_nilai')
            ->groupBy('santri_id')
            ->pluck('avg_nilai', 'santri_id');

        $labels = [];
        $data   = [];
        $colors = [];

        foreach ($santriList as $santri) {
            $nama    = $santri->nama_panggilan ?: explode(' ', $santri->nama_lengkap)[0];
            $hasData = isset($rows[$santri->id]);
            $avg     = (float) ($rows[$santri->id] ?? 0);

            $labels[] = $nama;
            $data[]   = $avg;
            $colors[] = ! $hasData
                ? '#9ca3af'
                : ($avg >= 80 ? '#10b981' : ($avg >= 65 ? '#f59e0b' : '#ef4444'));
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Rata-rata Nilai',
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
                    'ticks' => ['stepSize' => 10],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
