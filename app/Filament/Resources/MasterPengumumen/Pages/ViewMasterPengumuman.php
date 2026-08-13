<?php

namespace App\Filament\Resources\MasterPengumumen\Pages;

use App\Filament\Resources\MasterPengumumen\MasterPengumumanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewMasterPengumuman extends ViewRecord
{
    protected static string $resource = MasterPengumumanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => MasterPengumumanResource::canEdit($this->getRecord()))
                ->modalWidth(Width::TwoExtraLarge),
        ];
    }
}
