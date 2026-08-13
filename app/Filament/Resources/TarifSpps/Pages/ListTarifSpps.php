<?php

namespace App\Filament\Resources\TarifSpps\Pages;

use App\Filament\Resources\TarifSpps\TarifSppResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListTarifSpps extends ListRecords
{
    protected static string $resource = TarifSppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Tarif SPP')
                ->modalWidth(Width::Medium),
        ];
    }
}
