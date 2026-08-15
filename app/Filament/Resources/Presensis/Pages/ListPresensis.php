<?php

namespace App\Filament\Resources\Presensis\Pages;

use App\Filament\Pages\PresensiHarianPage;
use App\Filament\Pages\PresensiPengaturanPage;
use App\Filament\Resources\Presensis\PresensiResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListPresensis extends ListRecords
{
    protected static string $resource = PresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Kedua halaman sengaja tidak didaftarkan sebagai tab cluster (menu
            // Presensi cuma boleh berisi empat submenu); dua tombol ini jalan masuknya.
            Action::make('isiPresensi')
                ->label('Isi Presensi')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->url(PresensiHarianPage::getUrl())
                ->color('gray')
                ->visible(fn (): bool => PresensiHarianPage::canAccess()),

            Action::make('pengaturanPresensi')
                ->label('Pengaturan')
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->url(PresensiPengaturanPage::getUrl())
                ->color('gray')
                ->visible(fn (): bool => PresensiPengaturanPage::canAccess()),

            CreateAction::make()->modalWidth(Width::TwoExtraLarge),
        ];
    }
}
