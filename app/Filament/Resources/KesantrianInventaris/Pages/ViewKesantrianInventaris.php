<?php

namespace App\Filament\Resources\KesantrianInventaris\Pages;

use App\Filament\Resources\KesantrianInventaris\KesantrianInventarisResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKesantrianInventaris extends ViewRecord
{
    protected static string $resource = KesantrianInventarisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
