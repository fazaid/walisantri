<?php

namespace App\Filament\Resources\TahfidzUjians\Pages;

use App\Filament\Resources\TahfidzUjians\TahfidzUjianResource;
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
