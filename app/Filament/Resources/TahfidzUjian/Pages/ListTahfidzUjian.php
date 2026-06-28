<?php

namespace App\Filament\Resources\TahfidzUjian\Pages;

use App\Filament\Resources\TahfidzUjian\TahfidzUjianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTahfidzUjian extends ListRecords
{
    protected static string $resource = TahfidzUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
