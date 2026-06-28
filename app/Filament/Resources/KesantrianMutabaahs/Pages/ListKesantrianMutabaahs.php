<?php

namespace App\Filament\Resources\KesantrianMutabaahs\Pages;

use App\Filament\Resources\KesantrianMutabaahs\KesantrianMutabaahResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListKesantrianMutabaahs extends ListRecords
{
    protected static string $resource = KesantrianMutabaahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->modalHeading('Export Rekap Mutaba\'ah')
                ->modalSubmitActionLabel('Download')
                ->form([
                    Grid::make(2)->schema([
                        Select::make('bulan')
                            ->label('Bulan')
                            ->options(
                                collect(range(1, 12))
                                    ->mapWithKeys(fn ($m) => [$m => Carbon::create()->month($m)->translatedFormat('F')])
                                    ->toArray()
                            )
                            ->default(now()->month)
                            ->required(),
                        Select::make('tahun')
                            ->label('Tahun')
                            ->options(
                                collect(range(now()->year - 2, now()->year))
                                    ->mapWithKeys(fn ($y) => [$y => (string) $y])
                                    ->toArray()
                            )
                            ->default(now()->year)
                            ->required(),
                    ]),
                ])
                ->action(fn (array $data) => redirect()->to(route('admin.export.mutabaah', $data))),

            CreateAction::make(),
        ];
    }
}
