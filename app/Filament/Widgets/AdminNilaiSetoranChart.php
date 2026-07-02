<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminNilaiSetoranChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Nilai Setoran 7 Hari';

    protected static ?int $sort = 8;

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
            : 'Kualitas hafalan seluruh santri';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $pesantrenId = Auth::user()?->pesantren_id;

        $rows = DB::table('tahfidz_progress')
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
