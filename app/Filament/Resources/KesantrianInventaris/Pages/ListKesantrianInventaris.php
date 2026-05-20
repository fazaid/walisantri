<?php

namespace App\Filament\Resources\KesantrianInventaris\Pages;

use App\Filament\Resources\KesantrianInventaris\KesantrianInventarisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKesantrianInventaris extends ListRecords
{
    protected static string $resource = KesantrianInventarisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
