<?php

namespace App\Filament\Resources\EkskulMasters\Pages;

use App\Filament\Resources\EkskulMasters\EkskulMasterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEkskulMaster extends EditRecord
{
    protected static string $resource = EkskulMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
