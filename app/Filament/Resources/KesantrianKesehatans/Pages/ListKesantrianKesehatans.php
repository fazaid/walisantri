<?php

namespace App\Filament\Resources\KesantrianKesehatans\Pages;

use App\Filament\Resources\KesantrianKesehatans\KesantrianKesehatanResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Resources\Pages\ListRecords;

class ListKesantrianKesehatans extends ListRecords
{
    protected static string $resource = KesantrianKesehatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->modalHeading('Export Rekam Medis')
                ->modalSubmitActionLabel('Download')
                ->form([
                    Grid::make(2)->schema([
                        DatePicker::make('dari')
                            ->label('Dari Tanggal')
                            ->native(false),
                        DatePicker::make('sampai')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ]),
                ])
                ->action(fn (array $data) => redirect()->to(
                    route('admin.export.rekam-medis', array_filter($data))
                )),

            CreateAction::make(),
        ];
    }
}
