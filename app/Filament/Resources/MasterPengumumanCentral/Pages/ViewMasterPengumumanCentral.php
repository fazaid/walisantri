<?php

namespace App\Filament\Resources\MasterPengumumanCentral\Pages;

use App\Filament\Resources\MasterPengumumanCentral\MasterPengumumanCentralResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewMasterPengumumanCentral extends ViewRecord
{
    protected static string $resource = MasterPengumumanCentralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->modalWidth(Width::TwoExtraLarge),
        ];
    }
}
