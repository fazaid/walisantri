<?php

namespace App\Filament\Resources\PrestasiSantris\Pages;

use App\Filament\Resources\PrestasiSantris\PrestasiSantriResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListPrestasiSantris extends ListRecords
{
    protected static string $resource = PrestasiSantriResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->modalWidth(Width::FourExtraLarge)];
    }
}
