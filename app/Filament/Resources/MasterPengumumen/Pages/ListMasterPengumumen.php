<?php

namespace App\Filament\Resources\MasterPengumumen\Pages;

use App\Filament\Resources\MasterPengumumen\MasterPengumumanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListMasterPengumumen extends ListRecords
{
    protected static string $resource = MasterPengumumanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Filament tidak menyembunyikan tombol ini sendiri lewat canCreate(), jadi
            // tanpa penjaga ini ustadz (yang aksesnya baca saja) tetap melihat tombol
            // yang modalnya berujung ditolak. Pola sama dipakai di ListSantris.
            CreateAction::make()
                ->visible(fn (): bool => static::getResource()::canCreate())
                ->modalWidth(Width::TwoExtraLarge)
                ->mutateDataUsing(MasterPengumumanResource::tetapkanPemilik()),
        ];
    }
}
