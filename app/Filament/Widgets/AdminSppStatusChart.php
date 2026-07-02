<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TagihanSpps\TagihanSppResource;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class AdminSppStatusChart extends ChartWidget
{
    protected ?string $heading = 'Status SPP Bulan Ini';

    protected static ?int $sort = 11;

    // Grid dashboard Filament (Filament\Pages\Dashboard::getColumns()) cuma 1 kolom
    // eksplisit di bawah breakpoint lg (1024px), baru 2 kolom di lg+ — bukan 2 kolom
    // sejak awal seperti asumsi ['default'=>2,'md'=>1] sebelumnya. Asumsi lama bikin
    // widget minta grid-column:span 2 di grid ber-1-kolom pada mobile (<768px), CSS
    // Grid terpaksa bikin kolom implisit auto-size → overflow/scroll horizontal.
    // span 1 otomatis benar di semua breakpoint: full-width saat grid 1 kolom,
    // setengah-lebar (berpasangan) saat grid 2 kolom di lg+.
    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public function getDescription(): string|Htmlable|null
    {
        $rows = $this->rekapStatus();

        if ($rows['total'] === 0) {
            return 'Belum ada tagihan SPP untuk bulan ini.';
        }

        $tertunggak = $rows['nominal_belum_bayar'] + $rows['nominal_menunggu_konfirmasi'];
        $url        = TagihanSppResource::getUrl('index', [
            'tableFilters' => ['status' => ['value' => 'belum_bayar']],
        ]);

        return new HtmlString(
            'Tertunggak: <strong>' . $this->formatRupiah($tertunggak) . '</strong> · '
            . '<a href="' . e($url) . '" class="underline hover:no-underline">Lihat daftar &rarr;</a>'
        );
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /** @var array<string, int>|null Memoized dalam satu request — getDescription() & getData() sama-sama butuh ini. */
    private ?array $rekapStatusCache = null;

    private function rekapStatus(): array
    {
        if ($this->rekapStatusCache !== null) {
            return $this->rekapStatusCache;
        }

        $pesantrenId = Auth::user()?->pesantren_id;

        $rows = DB::table('tagihan_spp')
            ->where('pesantren_id', $pesantrenId)
            ->where('bulan', now()->month)
            ->where('tahun', now()->year)
            ->select('status', DB::raw('count(*) as total'), DB::raw('sum(nominal) as total_nominal'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $belum     = (int) ($rows['belum_bayar']->total ?? 0);
        $menunggu  = (int) ($rows['menunggu_konfirmasi']->total ?? 0);
        $lunas     = (int) ($rows['lunas']->total ?? 0);

        return $this->rekapStatusCache = [
            'belum'                       => $belum,
            'menunggu'                    => $menunggu,
            'lunas'                       => $lunas,
            'total'                       => $belum + $menunggu + $lunas,
            'nominal_belum_bayar'         => (int) ($rows['belum_bayar']->total_nominal ?? 0),
            'nominal_menunggu_konfirmasi' => (int) ($rows['menunggu_konfirmasi']->total_nominal ?? 0),
        ];
    }

    private function formatRupiah(int $amount): string
    {
        if ($amount >= 1_000_000_000) {
            return 'Rp ' . number_format($amount / 1_000_000_000, 1) . 'M';
        }
        if ($amount >= 1_000_000) {
            return 'Rp ' . number_format($amount / 1_000_000, 1) . 'Jt';
        }
        if ($amount >= 1_000) {
            return 'Rp ' . number_format($amount / 1_000, 0) . 'Rb';
        }
        return 'Rp ' . $amount;
    }

    protected function getData(): array
    {
        $rows = $this->rekapStatus();
        $belum     = $rows['belum'];
        $menunggu  = $rows['menunggu'];
        $lunas     = $rows['lunas'];

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
