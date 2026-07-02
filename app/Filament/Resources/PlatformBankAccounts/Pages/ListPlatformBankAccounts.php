<?php

namespace App\Filament\Resources\PlatformBankAccounts\Pages;

use App\Filament\Resources\PlatformBankAccounts\PlatformBankAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatformBankAccounts extends ListRecords
{
    protected static string $resource = PlatformBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
