<?php

namespace App\Filament\Resources\MasterPengumumen\Pages;

use App\Filament\Resources\MasterPengumumen\MasterPengumumanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasterPengumumen extends ListRecords
{
    protected static string $resource = MasterPengumumanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Filament hanya menegakkan canCreate() di halaman CreateRecord, jadi
            // tanpa penjaga ini ustadz (yang aksesnya baca saja) tetap melihat
            // tombol yang berujung 403. Pola sama dipakai di ListSantris.
            CreateAction::make()->visible(fn (): bool => static::getResource()::canCreate()),
        ];
    }
}
