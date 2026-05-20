<?php

namespace App\Filament\Resources\TahfidzUjians\Pages;

use App\Filament\Resources\TahfidzUjians\TahfidzUjianResource;
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
