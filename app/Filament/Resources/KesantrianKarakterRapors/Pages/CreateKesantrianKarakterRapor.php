<?php

namespace App\Filament\Resources\KesantrianKarakterRapors\Pages;

use App\Filament\Resources\KesantrianKarakterRapors\KesantrianKarakterRaporResource;
use App\Models\KesantrianKarakterRapor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateKesantrianKarakterRapor extends CreateRecord
{
    protected static string $resource = KesantrianKarakterRaporResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->data;

        $exists = KesantrianKarakterRapor::where('santri_id', $data['santri_id'])
            ->where('tahun_ajaran', $data['tahun_ajaran'])
            ->where('periode', $data['periode'])
            ->where('bulan', $data['bulan'] ?? null)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Data sudah ada')
                ->body('Rapor karakter santri ini untuk periode tersebut sudah pernah diinput.')
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
