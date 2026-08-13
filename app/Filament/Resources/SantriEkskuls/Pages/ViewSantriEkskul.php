<?php

namespace App\Filament\Resources\SantriEkskuls\Pages;

use App\Filament\Resources\SantriEkskuls\SantriEkskulResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewSantriEkskul extends ViewRecord
{
    protected static string $resource = SantriEkskulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->modalWidth(Width::TwoExtraLarge)
                ->before(SantriEkskulResource::guardDuplikat()),
            DeleteAction::make(),
        ];
    }
}
