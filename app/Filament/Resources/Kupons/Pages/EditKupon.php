<?php

namespace App\Filament\Resources\Kupons\Pages;

use App\Filament\Resources\Kupons\KuponResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKupon extends EditRecord
{
    protected static string $resource = KuponResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
