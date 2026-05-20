<?php

namespace App\Filament\Resources\Pesantrens\Pages;

use App\Filament\Resources\Pesantrens\PesantrenResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPesantren extends ViewRecord
{
    protected static string $resource = PesantrenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
