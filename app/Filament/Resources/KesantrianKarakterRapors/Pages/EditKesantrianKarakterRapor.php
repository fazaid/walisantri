<?php

namespace App\Filament\Resources\KesantrianKarakterRapors\Pages;

use App\Filament\Resources\KesantrianKarakterRapors\KesantrianKarakterRaporResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditKesantrianKarakterRapor extends EditRecord
{
    protected static string $resource = KesantrianKarakterRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
