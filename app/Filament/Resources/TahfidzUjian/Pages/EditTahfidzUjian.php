<?php

namespace App\Filament\Resources\TahfidzUjian\Pages;

use App\Filament\Resources\TahfidzUjian\TahfidzUjianResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTahfidzUjian extends EditRecord
{
    protected static string $resource = TahfidzUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
