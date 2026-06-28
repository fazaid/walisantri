<?php

namespace App\Filament\Resources\UangSakus\Pages;

use App\Filament\Resources\UangSakus\UangSakuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUangSakus extends ListRecords
{
    protected static string $resource = UangSakuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Transaksi'),
        ];
    }
}
