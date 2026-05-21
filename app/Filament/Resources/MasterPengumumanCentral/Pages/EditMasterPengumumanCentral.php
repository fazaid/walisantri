<?php

namespace App\Filament\Resources\MasterPengumumanCentral\Pages;

use App\Filament\Resources\MasterPengumumanCentral\MasterPengumumanCentralResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMasterPengumumanCentral extends EditRecord
{
    protected static string $resource = MasterPengumumanCentralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
