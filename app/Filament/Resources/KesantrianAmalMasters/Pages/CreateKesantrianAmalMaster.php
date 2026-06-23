<?php

namespace App\Filament\Resources\KesantrianAmalMasters\Pages;

use App\Filament\Resources\KesantrianAmalMasters\KesantrianAmalMasterResource;
use App\Models\KesantrianAmalMaster;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateKesantrianAmalMaster extends CreateRecord
{
    protected static string $resource = KesantrianAmalMasterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $base = Str::slug($data['label'], '_') ?: 'amal';
        $kode = $base;
        $suffix = 1;

        while (
            KesantrianAmalMaster::where('pesantren_id', Auth::user()?->pesantren_id)
                ->where('kode', $kode)
                ->exists()
        ) {
            $kode = $base.'_'.(++$suffix);
        }

        $data['kode'] = $kode;

        return $data;
    }
}
