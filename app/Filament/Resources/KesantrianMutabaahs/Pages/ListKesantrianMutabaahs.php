<?php

namespace App\Filament\Resources\KesantrianMutabaahs\Pages;

use App\Filament\Resources\KesantrianMutabaahs\KesantrianMutabaahResource;
use App\Support\Waktu;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;

class ListKesantrianMutabaahs extends ListRecords
{
    protected static string $resource = KesantrianMutabaahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->modalHeading('Ekspor Rekap Mutaba\'ah')
                ->modalSubmitActionLabel('Unduh')
                ->form([
                    Grid::make(2)->schema([
                        Select::make('bulan')
                            ->label('Bulan')
                            ->options(
                                collect(range(1, 12))
                                    ->mapWithKeys(fn ($m) => [$m => Carbon::create()->month($m)->translatedFormat('F')])
                                    ->toArray()
                            )
                            ->default(Waktu::sekarang()->month)
                            ->required(),
                        Select::make('tahun')
                            ->label('Tahun')
                            ->options(
                                collect(range(Waktu::sekarang()->year - 2, Waktu::sekarang()->year))
                                    ->mapWithKeys(fn ($y) => [$y => (string) $y])
                                    ->toArray()
                            )
                            ->default(Waktu::sekarang()->year)
                            ->required(),
                    ]),
                ])
                ->action(fn (array $data) => redirect()->to(route('admin.export.mutabaah', $data))),

            CreateAction::make()
                ->modalWidth(Width::FourExtraLarge)
                ->using(KesantrianMutabaahResource::simpanAtauPerbarui()),
        ];
    }
}
