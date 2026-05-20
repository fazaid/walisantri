<?php

namespace App\Filament\Resources\KesantrianKesehatans\Pages;

use App\Filament\Resources\KesantrianKesehatans\KesantrianKesehatanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKesantrianKesehatan extends ViewRecord
{
    protected static string $resource = KesantrianKesehatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
