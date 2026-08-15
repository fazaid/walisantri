<?php

namespace App\Filament\Resources\Presensis\Pages;

use App\Filament\Resources\Presensis\PresensiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewPresensi extends ViewRecord
{
    protected static string $resource = PresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->modalWidth(Width::TwoExtraLarge),
            DeleteAction::make()
                ->visible(fn (): bool => PresensiResource::canDelete($this->getRecord())),
        ];
    }
}
