<?php

namespace App\Filament\Widgets;

use App\Enums\Modul;
use App\Models\KesantrianMutabaah;
use App\Models\Santri;
use App\Services\MutabaahScoreCalculator;
use App\Support\Waktu;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class UstadzAmalanChart extends ChartWidget
{
    // Matikan polling default CanPoll (5s): dashboard agregat tak perlu refresh live,
    // dan request polling latar jadi sumber toast error saat wake-from-sleep.
    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Amalan per Santri — 7 Hari';

    protected static ?int $sort = 50;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'ustadz'
            && Modul::Kesantrian->aktif();
    }

    public function getDescription(): string|Htmlable|null
    {
        $labels = $this->getCachedData()['labels'] ?? [];

        return empty($labels)
            ? 'Belum ada data mutabaah yang diinput dalam 7 hari terakhir.'
            : 'Persentase amalan tiap santri Anda';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $ustadzId = Auth::id();
        $pesantrenId = Auth::user()?->pesantren_id;

        $santriList = Santri::where('pesantren_id', $pesantrenId)
            ->where('pembimbing_ustadz_id', $ustadzId)
            ->where('status_aktif', true)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nama_panggilan']);

        if ($santriList->isEmpty()) {
            return ['datasets' => [['label' => '', 'data' => []]], 'labels' => []];
        }

        $start = Waktu::sekarang()->subDays(6)->toDateString();
        $end = Waktu::sekarang()->toDateString();

        $allMutabaah = KesantrianMutabaah::whereIn('santri_id', $santriList->pluck('id'))
            ->whereBetween('tanggal', [$start, $end])
            ->get()
            ->groupBy('santri_id');

        if ($allMutabaah->isEmpty()) {
            return ['datasets' => [['label' => '', 'data' => []]], 'labels' => []];
        }

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($santriList as $santri) {
            $list = $allMutabaah->get($santri->id, collect());
            $pct = MutabaahScoreCalculator::persentaseRataRata($list);

            // Nama pendek untuk label chart
            $nama = $santri->nama_panggilan
                ?: explode(' ', $santri->nama_lengkap)[0];

            $labels[] = $nama;
            $data[] = $pct;
            $colors[] = $pct >= 75 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
        }

        return [
            'datasets' => [
                [
                    'label' => '% Amalan',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return RawJs::make("{
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    ticks: { callback: (v) => v + '%' }
                }
            },
            plugins: { legend: { display: false } }
        }");
    }
}
