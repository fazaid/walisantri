<?php

namespace App\Filament\Resources\KesantrianKesehatans\Pages;

use App\Filament\Resources\KesantrianKesehatans\KesantrianKesehatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKesantrianKesehatans extends ListRecords
{
    protected static string $resource = KesantrianKesehatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
