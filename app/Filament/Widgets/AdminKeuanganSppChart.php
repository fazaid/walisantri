<?php

namespace App\Filament\Widgets;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminKeuanganSppChart extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Keuangan SPP per Bulan';

    protected ?string $description = 'Total tagihan vs terkumpul sepanjang tahun';

    protected static ?int $sort = 15;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tahun')
                ->label('Tahun')
                ->options(fn () => [
                    (string) now()->year       => (string) now()->year,
                    (string) (now()->year - 1) => (string) (now()->year - 1),
                ])
                ->default((string) now()->year),
        ]);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $tahun       = (int) ($this->filters['tahun'] ?? now()->year);
        $pesantrenId = Auth::user()?->pesantren_id;

        $terkumpul = DB::table('pembayaran_spp')
            ->where('pesantren_id', $pesantrenId)
            ->whereYear('tanggal_bayar', $tahun)
            ->selectRaw('EXTRACT(MONTH FROM tanggal_bayar)::integer AS bulan, SUM(jumlah) AS total')
            ->groupByRaw('EXTRACT(MONTH FROM tanggal_bayar)')
            ->pluck('total', 'bulan');

        $tagihan = DB::table('tagihan_spp')
            ->where('pesantren_id', $pesantrenId)
            ->where('tahun', $tahun)
            ->selectRaw('bulan, SUM(nominal) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $labels       = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $dataTerkumpul = [];
        $dataTagihan   = [];

        for ($m = 1; $m <= 12; $m++) {
            $dataTerkumpul[] = (int) ($terkumpul[$m] ?? 0);
            $dataTagihan[]   = (int) ($tagihan[$m] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Terkumpul',
                    'data'            => $dataTerkumpul,
                    'backgroundColor' => '#10b981',
                    'borderRadius'    => 4,
                ],
                [
                    'label'           => 'Total Tagihan',
                    'data'            => $dataTagihan,
                    'backgroundColor' => '#94a3b8',
                    'borderRadius'    => 4,
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
                    'ticks'       => [
                        'callback' => RawJs::make(
                            "(v) => { if (v >= 1e9) return 'Rp ' + (v/1e9).toFixed(1) + 'M'; if (v >= 1e6) return 'Rp ' + (v/1e6).toFixed(1) + 'Jt'; if (v >= 1e3) return 'Rp ' + (v/1e3).toFixed(0) + 'Rb'; return 'Rp ' + v; }"
                        ),
                    ],
                ],
            ],
            'plugins' => [
                'legend' => ['position' => 'top'],
            ],
        ];
    }
}
