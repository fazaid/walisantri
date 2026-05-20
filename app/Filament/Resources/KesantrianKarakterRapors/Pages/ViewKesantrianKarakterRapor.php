<?php

namespace App\Filament\Resources\KesantrianKarakterRapors\Pages;

use App\Filament\Resources\KesantrianKarakterRapors\KesantrianKarakterRaporResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKesantrianKarakterRapor extends ViewRecord
{
    protected static string $resource = KesantrianKarakterRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
