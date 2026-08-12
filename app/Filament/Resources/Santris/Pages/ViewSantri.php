<?php

namespace App\Filament\Resources\Santris\Pages;

use App\Filament\Resources\Santris\Actions\KirimMagicLinkAction;
use App\Filament\Resources\Santris\Actions\PreviewSebagaiWaliAction;
use App\Filament\Resources\Santris\Actions\RegenerasiUuidAction;
use App\Filament\Resources\Santris\SantriResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewSantri extends ViewRecord
{
    protected static string $resource = SantriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            KirimMagicLinkAction::make(),
            PreviewSebagaiWaliAction::make(),
            RegenerasiUuidAction::make(),
            EditAction::make()->modalWidth(Width::FourExtraLarge),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
