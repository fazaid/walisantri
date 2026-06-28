<?php

namespace App\Filament\Widgets;

use App\Models\KesantrianKesehatan;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Facades\Auth;

class AdminKesehatanTrendChart extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Tren Insiden Kesehatan';

    protected ?string $description = 'Jumlah kunjungan klinik per hari';

    protected static ?int $sort = 25;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period')
                ->label('Periode')
                ->options([
                    '7'  => '7 Hari',
                    '14' => '14 Hari',
                    '30' => '30 Hari',
                ])
                ->default('30'),
        ]);
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days  = (int) ($this->filters['period'] ?? 30);
        $start = now()->subDays($days - 1)->toDateString();

        $rows = KesantrianKesehatan::whereBetween('tanggal_periksa', [$start, now()->toDateString()])
            ->get(['tanggal_periksa'])
            ->groupBy(fn ($r) => $r->tanggal_periksa->toDateString())
            ->map->count();

        $labels = [];
        $data   = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('d/m');
            $data[]   = (int) ($rows[$date] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'            => 'Insiden Kesehatan',
                    'data'             => $data,
                    'borderColor'      => '#f97316',
                    'backgroundColor'  => '#f9731622',
                    'fill'             => true,
                    'tension'          => 0.4,
                    'pointRadius'      => 3,
                    'pointHoverRadius' => 5,
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
