<?php

namespace App\Filament\Resources\Pesantrens\Pages;

use App\Filament\Resources\Pesantrens\PesantrenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPesantrens extends ListRecords
{
    protected static string $resource = PesantrenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
