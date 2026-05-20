<?php

namespace App\Filament\Resources\KesantrianKesehatans\Pages;

use App\Filament\Resources\KesantrianKesehatans\KesantrianKesehatanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditKesantrianKesehatan extends EditRecord
{
    protected static string $resource = KesantrianKesehatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
