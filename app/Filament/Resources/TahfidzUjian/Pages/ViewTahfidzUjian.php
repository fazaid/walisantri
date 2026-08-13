<?php

namespace App\Filament\Resources\TahfidzUjian\Pages;

use App\Filament\Resources\TahfidzUjian\TahfidzUjianResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewTahfidzUjian extends ViewRecord
{
    protected static string $resource = TahfidzUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->modalWidth(Width::FourExtraLarge),
            DeleteAction::make()
                ->visible(fn (): bool => TahfidzUjianResource::canDelete($this->getRecord())),
        ];
    }
}
