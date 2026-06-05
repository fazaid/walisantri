<?php

namespace App\Filament\Resources\TagihanSpps\Pages;

use App\Filament\Resources\TagihanSpps\TagihanSppResource;
use Filament\Resources\Pages\ListRecords;

class ListTagihanSpps extends ListRecords
{
    protected static string $resource = TagihanSppResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
