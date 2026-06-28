<?php

namespace App\Filament\Resources\SantriEkskuls\Pages;

use App\Filament\Resources\SantriEkskuls\SantriEkskulResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSantriEkskul extends EditRecord
{
    protected static string $resource = SantriEkskulResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
