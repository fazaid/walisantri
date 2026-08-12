<?php

namespace App\Filament\Resources\NilaiAkademiks\Pages;

use App\Filament\Pages\NilaiMassalPage;
use App\Filament\Resources\NilaiAkademiks\NilaiAkademikResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListNilaiAkademik extends ListRecords
{
    protected static string $resource = NilaiAkademikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::TwoExtraLarge)
                ->before(NilaiAkademikResource::guardDuplikat()),
            // NilaiMassalPage sengaja tidak didaftarkan di navigasi; tombol ini
            // satu-satunya jalan masuknya.
            Action::make('inputMassal')
                ->label('Input Nilai Massal')
                ->icon(Heroicon::OutlinedTableCells)
                ->url(NilaiMassalPage::getUrl())
                ->color('gray')
                ->visible(fn (): bool => NilaiMassalPage::canAccess()),
        ];
    }
}
