<?php

namespace App\Filament\Resources\Santris\Pages;

use App\Filament\Resources\Santris\Actions\KirimMagicLinkAction;
use App\Filament\Resources\Santris\Actions\RegenerasiUuidAction;
use App\Filament\Resources\Santris\SantriResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSantri extends ViewRecord
{
    protected static string $resource = SantriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            KirimMagicLinkAction::make(),
            RegenerasiUuidAction::make(),
            EditAction::make(),
        ];
    }
}
