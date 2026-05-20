<?php

namespace App\Filament\Resources\MasterPengumumen\Pages;

use App\Filament\Resources\MasterPengumumen\MasterPengumumanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasterPengumumen extends ListRecords
{
    protected static string $resource = MasterPengumumanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
