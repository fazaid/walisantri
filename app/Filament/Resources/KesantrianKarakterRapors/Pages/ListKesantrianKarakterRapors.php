<?php

namespace App\Filament\Resources\KesantrianKarakterRapors\Pages;

use App\Filament\Resources\KesantrianKarakterRapors\KesantrianKarakterRaporResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKesantrianKarakterRapors extends ListRecords
{
    protected static string $resource = KesantrianKarakterRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
