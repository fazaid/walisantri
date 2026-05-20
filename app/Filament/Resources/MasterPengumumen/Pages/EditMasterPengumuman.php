<?php

namespace App\Filament\Resources\MasterPengumumen\Pages;

use App\Filament\Resources\MasterPengumumen\MasterPengumumanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMasterPengumuman extends EditRecord
{
    protected static string $resource = MasterPengumumanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
