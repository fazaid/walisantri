<?php

namespace App\Filament\Resources\TahfidzRapors\Pages;

use App\Filament\Resources\TahfidzRapors\TahfidzRaporResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTahfidzRapor extends ViewRecord
{
    protected static string $resource = TahfidzRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
