<?php

namespace App\Filament\Resources\SantriEkskuls\Pages;

use App\Filament\Resources\SantriEkskuls\SantriEkskulResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSantriEkskuls extends ListRecords
{
    protected static string $resource = SantriEkskulResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
