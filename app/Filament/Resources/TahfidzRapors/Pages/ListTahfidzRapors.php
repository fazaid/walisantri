<?php

namespace App\Filament\Resources\TahfidzRapors\Pages;

use App\Filament\Resources\TahfidzRapors\TahfidzRaporResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTahfidzRapors extends ListRecords
{
    protected static string $resource = TahfidzRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
