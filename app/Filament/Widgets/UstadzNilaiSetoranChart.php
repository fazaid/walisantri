<?php

namespace App\Filament\Widgets;

use App\Models\Santri;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UstadzNilaiSetoranChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Nilai Setoran 7 Hari';

    protected ?string $description = 'Kualitas hafalan santri halaqah Anda';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = ['default' => 2, 'md' => 1];

    protected ?string $maxHeight = '260px';

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

        $santriIds = Santri::where('pesantren_id', $pesantrenId)
            ->where('pembimbing_ustadz_id', $ustadzId)
            ->where('status_aktif', true)
            ->pluck('id');

        if ($santriIds->isEmpty()) {
            return ['datasets' => [['label' => '', 'data' => []]], 'labels' => []];
        }

        $rows = DB::table('tahfidz_progress')
            ->whereIn('santri_id', $santriIds)
            ->where('pesantren_id', $pesantrenId)
            ->whereBetween('tanggal', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->select('nilai_kelancaran', DB::raw('count(*) as total'))
            ->groupBy('nilai_kelancaran')
            ->pluck('total', 'nilai_kelancaran');

        // Urutan tetap dari terbaik ke cukup
        $order  = ['Mumtaz', 'Jayyid Jiddan', 'Jayyid', 'Maqbul'];
        $colors = ['#10b981', '#14b8a6', '#3b82f6', '#f59e0b'];

        $data = array_map(fn ($label) => (int) ($rows[$label] ?? 0), $order);

        return [
            'datasets' => [
                [
                    'label'           => 'Jumlah Setoran',
                    'data'            => $data,
                    'backgroundColor' => $colors,
                    'borderRadius'    => 6,
                ],
            ],
            'labels' => $order,
        ];
    }

    protected function getOptions(): array|\Filament\Support\RawJs|null
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
