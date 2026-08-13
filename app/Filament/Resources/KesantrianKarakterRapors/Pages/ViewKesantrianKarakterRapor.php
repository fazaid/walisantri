<?php

namespace App\Filament\Resources\KesantrianKarakterRapors\Pages;

use App\Filament\Resources\KesantrianKarakterRapors\KesantrianKarakterRaporResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewKesantrianKarakterRapor extends ViewRecord
{
    protected static string $resource = KesantrianKarakterRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->modalWidth(Width::FourExtraLarge)
                ->before(KesantrianKarakterRaporResource::guardDuplikat()),
            DeleteAction::make()
                ->visible(fn (): bool => KesantrianKarakterRaporResource::canDelete($this->getRecord())),
        ];
    }
}
