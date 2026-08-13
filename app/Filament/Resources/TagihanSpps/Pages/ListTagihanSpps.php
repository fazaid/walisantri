<?php

namespace App\Filament\Resources\TagihanSpps\Pages;

use App\Filament\Resources\TagihanSpps\TagihanSppResource;
use App\Filament\Resources\TarifSpps\TarifSppResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTagihanSpps extends ListRecords
{
    protected static string $resource = TagihanSppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // TarifSppResource sengaja tidak didaftarkan di tab Keuangan;
            // tombol ini satu-satunya jalan masuknya.
            Action::make('tarifSpp')
                ->label('Tarif SPP')
                ->icon(Heroicon::OutlinedTableCells)
                ->url(TarifSppResource::getUrl('index'))
                ->color('gray')
                ->visible(fn (): bool => TarifSppResource::canAccess()),
        ];
    }
}
