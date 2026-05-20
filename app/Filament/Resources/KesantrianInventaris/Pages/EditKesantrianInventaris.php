<?php

namespace App\Filament\Resources\KesantrianInventaris\Pages;

use App\Filament\Resources\KesantrianInventaris\KesantrianInventarisResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditKesantrianInventaris extends EditRecord
{
    protected static string $resource = KesantrianInventarisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
