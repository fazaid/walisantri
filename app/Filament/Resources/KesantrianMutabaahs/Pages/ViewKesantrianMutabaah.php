<?php

namespace App\Filament\Resources\KesantrianMutabaahs\Pages;

use App\Filament\Resources\KesantrianMutabaahs\KesantrianMutabaahResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKesantrianMutabaah extends ViewRecord
{
    protected static string $resource = KesantrianMutabaahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
