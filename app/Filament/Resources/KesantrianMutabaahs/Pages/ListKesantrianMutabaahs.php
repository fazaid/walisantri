<?php

namespace App\Filament\Resources\KesantrianMutabaahs\Pages;

use App\Filament\Resources\KesantrianMutabaahs\KesantrianMutabaahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKesantrianMutabaahs extends ListRecords
{
    protected static string $resource = KesantrianMutabaahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
