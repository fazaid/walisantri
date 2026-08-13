<?php

namespace App\Filament\Resources\KesantrianAmalMasters\Pages;

use App\Filament\Resources\KesantrianAmalMasters\KesantrianAmalMasterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListKesantrianAmalMaster extends ListRecords
{
    protected static string $resource = KesantrianAmalMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::TwoExtraLarge)
                ->mutateDataUsing(KesantrianAmalMasterResource::isiKodeOtomatis()),
        ];
    }
}
