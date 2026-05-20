<?php

namespace App\Filament\Resources\Pesantrens\Pages;

use App\Filament\Resources\Pesantrens\PesantrenResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPesantren extends EditRecord
{
    protected static string $resource = PesantrenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
