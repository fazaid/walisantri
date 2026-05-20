<?php

namespace App\Filament\Resources\TahfidzRapors\Pages;

use App\Filament\Resources\TahfidzRapors\TahfidzRaporResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTahfidzRapor extends EditRecord
{
    protected static string $resource = TahfidzRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
