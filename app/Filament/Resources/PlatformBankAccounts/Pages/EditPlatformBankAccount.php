<?php

namespace App\Filament\Resources\PlatformBankAccounts\Pages;

use App\Filament\Resources\PlatformBankAccounts\PlatformBankAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlatformBankAccount extends EditRecord
{
    protected static string $resource = PlatformBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
