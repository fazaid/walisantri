<?php

namespace App\Filament\Resources\TahfidzProgress\Pages;

use App\Filament\Resources\TahfidzProgress\TahfidzProgressResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewTahfidzProgress extends ViewRecord
{
    protected static string $resource = TahfidzProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->modalWidth(Width::FourExtraLarge),
            DeleteAction::make()
                ->visible(fn (): bool => TahfidzProgressResource::canDelete($this->getRecord())),
        ];
    }
}
