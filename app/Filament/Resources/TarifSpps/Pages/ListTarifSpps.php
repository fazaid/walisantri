<?php

namespace App\Filament\Resources\TarifSpps\Pages;

use App\Filament\Resources\TarifSpps\TarifSppResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTarifSpps extends ListRecords
{
    protected static string $resource = TarifSppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
