<?php

namespace App\Filament\Resources\PlatformBankAccounts\Pages;

use App\Filament\Resources\PlatformBankAccounts\PlatformBankAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListPlatformBankAccounts extends ListRecords
{
    protected static string $resource = PlatformBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth(Width::Medium),
        ];
    }
}
