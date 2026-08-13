<?php

namespace App\Filament\Resources\KesantrianKesehatans\Pages;

use App\Filament\Resources\KesantrianKesehatans\KesantrianKesehatanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewKesantrianKesehatan extends ViewRecord
{
    protected static string $resource = KesantrianKesehatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->modalWidth(Width::FourExtraLarge),
            DeleteAction::make()
                ->visible(fn (): bool => KesantrianKesehatanResource::canDelete($this->getRecord())),
        ];
    }
}
