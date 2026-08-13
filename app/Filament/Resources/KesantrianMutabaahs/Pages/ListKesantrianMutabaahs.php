<?php

namespace App\Filament\Resources\KesantrianMutabaahs\Pages;

use App\Filament\Pages\MutabaahHarianPage;
use App\Filament\Resources\KesantrianAmalMasters\KesantrianAmalMasterResource;
use App\Filament\Resources\KesantrianMutabaahs\KesantrianMutabaahResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListKesantrianMutabaahs extends ListRecords
{
    protected static string $resource = KesantrianMutabaahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // MutabaahHarianPage & KesantrianAmalMasterResource sengaja tidak lagi
            // didaftarkan sebagai tab cluster; dua tombol ini jalan masuknya.
            Action::make('isiHarian')
                ->label('Isi Harian')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->url(MutabaahHarianPage::getUrl())
                ->color('gray')
                ->visible(fn (): bool => MutabaahHarianPage::canAccess()),

            Action::make('pengaturanAmal')
                ->label('Amal')
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->url(KesantrianAmalMasterResource::getUrl('index'))
                ->color('gray')
                ->visible(fn (): bool => KesantrianAmalMasterResource::canAccess()),

            CreateAction::make()
                ->modalWidth(Width::FourExtraLarge)
                ->using(KesantrianMutabaahResource::simpanAtauPerbarui()),
        ];
    }
}
