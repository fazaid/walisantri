<?php

namespace App\Filament\Resources\UangSakus\Pages;

use App\Filament\Resources\UangSakus\UangSakuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListUangSakus extends ListRecords
{
    protected static string $resource = UangSakuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Transaksi')
                ->modalWidth(Width::Medium)
                ->mutateDataUsing(UangSakuResource::catatPencatat()),
        ];
    }
}
