<?php

namespace App\Filament\Resources\SantriEkskuls\Pages;

use App\Filament\Resources\EkskulMasters\EkskulMasterResource;
use App\Filament\Resources\SantriEkskuls\SantriEkskulResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListSantriEkskuls extends ListRecords
{
    protected static string $resource = SantriEkskulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::TwoExtraLarge)
                ->before(SantriEkskulResource::guardDuplikat()),
            // EkskulMasterResource sengaja tidak didaftarkan di navigasi;
            // tombol ini satu-satunya jalan masuknya. visible() wajib —
            // Ekskul Santri boleh diakses ustadz, master ekskul tidak.
            Action::make('kelolaEkskul')
                ->label('Kelola Ekskul')
                ->icon(Heroicon::OutlinedTrophy)
                ->url(EkskulMasterResource::getUrl('index'))
                ->color('gray')
                ->visible(fn (): bool => EkskulMasterResource::canAccess()),
        ];
    }
}
