<?php

namespace App\Filament\Resources\TahfidzUjian\Pages;

use App\Filament\Resources\TahfidzUjian\TahfidzUjianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListTahfidzUjian extends ListRecords
{
    protected static string $resource = TahfidzUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::FourExtraLarge)
                ->mutateDataUsing(TahfidzUjianResource::stempelPenguji()),
        ];
    }
}
