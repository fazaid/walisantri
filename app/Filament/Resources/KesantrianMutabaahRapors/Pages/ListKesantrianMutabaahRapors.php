<?php

namespace App\Filament\Resources\KesantrianMutabaahRapors\Pages;

use App\Filament\Resources\KesantrianMutabaahRapors\KesantrianMutabaahRaporResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKesantrianMutabaahRapors extends ListRecords
{
    protected static string $resource = KesantrianMutabaahRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
