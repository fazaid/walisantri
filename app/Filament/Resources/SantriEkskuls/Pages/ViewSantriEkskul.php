<?php

namespace App\Filament\Resources\SantriEkskuls\Pages;

use App\Filament\Resources\SantriEkskuls\SantriEkskulResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSantriEkskul extends ViewRecord
{
    protected static string $resource = SantriEkskulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
