<?php

namespace App\Filament\Resources\TahfidzUjian\Pages;

use App\Filament\Resources\TahfidzUjian\TahfidzUjianResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTahfidzUjian extends ViewRecord
{
    protected static string $resource = TahfidzUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
