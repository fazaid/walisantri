<?php

namespace App\Filament\Resources\EkskulMasters\Pages;

use App\Filament\Resources\EkskulMasters\EkskulMasterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEkskulMasters extends ListRecords
{
    protected static string $resource = EkskulMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
