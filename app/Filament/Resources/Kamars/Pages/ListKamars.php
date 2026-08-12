<?php

namespace App\Filament\Resources\Kamars\Pages;

use App\Filament\Resources\Kamars\KamarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListKamars extends ListRecords
{
    protected static string $resource = KamarResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->modalWidth(Width::Medium)];
    }
}
