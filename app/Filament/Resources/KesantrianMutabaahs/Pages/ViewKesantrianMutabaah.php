<?php

namespace App\Filament\Resources\KesantrianMutabaahs\Pages;

use App\Filament\Resources\KesantrianMutabaahs\KesantrianMutabaahResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewKesantrianMutabaah extends ViewRecord
{
    protected static string $resource = KesantrianMutabaahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->modalWidth(Width::FourExtraLarge)
                ->before(KesantrianMutabaahResource::guardTanggalBentrok()),
            DeleteAction::make()
                ->visible(fn (): bool => KesantrianMutabaahResource::canDelete($this->getRecord())),
        ];
    }
}
