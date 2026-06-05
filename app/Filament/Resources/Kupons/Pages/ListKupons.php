<?php

namespace App\Filament\Resources\Kupons\Pages;

use App\Filament\Resources\Kupons\KuponResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKupons extends ListRecords
{
    protected static string $resource = KuponResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
