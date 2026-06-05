<?php

namespace App\Filament\Resources\KesantrianMutabaahs\Pages;

use App\Filament\Resources\KesantrianMutabaahs\KesantrianMutabaahResource;
use App\Models\KesantrianMutabaah;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateKesantrianMutabaah extends CreateRecord
{
    protected static string $resource = KesantrianMutabaahResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return KesantrianMutabaah::updateOrCreate(
            ['santri_id' => $data['santri_id'], 'tanggal' => $data['tanggal']],
            $data,
        );
    }
}
