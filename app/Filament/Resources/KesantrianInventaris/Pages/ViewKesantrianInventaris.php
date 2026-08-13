<?php

namespace App\Filament\Resources\KesantrianInventaris\Pages;

use App\Filament\Resources\KesantrianInventaris\KesantrianInventarisResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewKesantrianInventaris extends ViewRecord
{
    protected static string $resource = KesantrianInventarisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->modalWidth(Width::FourExtraLarge),
            DeleteAction::make()
                ->visible(fn (): bool => KesantrianInventarisResource::canDelete($this->getRecord())),
        ];
    }
}
