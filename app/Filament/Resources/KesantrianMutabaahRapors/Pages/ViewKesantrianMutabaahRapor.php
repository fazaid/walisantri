<?php

namespace App\Filament\Resources\KesantrianMutabaahRapors\Pages;

use App\Filament\Resources\KesantrianMutabaahRapors\KesantrianMutabaahRaporResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKesantrianMutabaahRapor extends ViewRecord
{
    protected static string $resource = KesantrianMutabaahRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
