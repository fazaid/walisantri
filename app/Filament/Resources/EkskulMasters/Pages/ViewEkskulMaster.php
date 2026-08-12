<?php

namespace App\Filament\Resources\EkskulMasters\Pages;

use App\Filament\Resources\EkskulMasters\EkskulMasterResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEkskulMaster extends ViewRecord
{
    protected static string $resource = EkskulMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
