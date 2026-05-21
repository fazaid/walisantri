<?php

namespace App\Filament\Resources\MasterPengumumanCentral\Pages;

use App\Filament\Resources\MasterPengumumanCentral\MasterPengumumanCentralResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasterPengumumanCentral extends ListRecords
{
    protected static string $resource = MasterPengumumanCentralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
