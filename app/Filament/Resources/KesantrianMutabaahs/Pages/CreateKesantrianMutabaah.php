<?php

namespace App\Filament\Resources\KesantrianMutabaahs\Pages;

use App\Filament\Resources\KesantrianMutabaahs\KesantrianMutabaahResource;
use App\Models\KesantrianMutabaah;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateKesantrianMutabaah extends CreateRecord
{
    protected static string $resource = KesantrianMutabaahResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $sudahAda = KesantrianMutabaah::where('santri_id', $data['santri_id'])
            ->where('tanggal', $data['tanggal'])
            ->exists();

        $record = KesantrianMutabaah::updateOrCreate(
            ['santri_id' => $data['santri_id'], 'tanggal' => $data['tanggal']],
            $data,
        );

        if ($sudahAda) {
            Notification::make()
                ->title('Data diperbarui, bukan ditambah baru')
                ->body('Mutaba\'ah santri ini untuk tanggal tersebut sudah ada sebelumnya, sehingga data lama diperbarui dengan isian ini.')
                ->warning()
                ->send();
        }

        return $record;
    }
}
