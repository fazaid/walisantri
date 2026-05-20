<?php

namespace App\Filament\Resources\TahfidzUjians\Pages;

use App\Filament\Resources\TahfidzUjians\TahfidzUjianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTahfidzUjians extends ListRecords
{
    protected static string $resource = TahfidzUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
