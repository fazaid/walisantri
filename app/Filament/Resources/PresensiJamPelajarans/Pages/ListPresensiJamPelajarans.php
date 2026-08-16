<?php

namespace App\Filament\Resources\PresensiJamPelajarans\Pages;

use App\Filament\Resources\PresensiJamPelajarans\PresensiJamPelajaranResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPresensiJamPelajarans extends ListRecords
{
    protected static string $resource = PresensiJamPelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Jam'),
        ];
    }
}
