<?php

namespace App\Filament\Resources\KesantrianMutabaahRapors\Pages;

use App\Filament\Resources\KesantrianMutabaahRapors\KesantrianMutabaahRaporResource;
use App\Models\KesantrianMutabaahRapor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateKesantrianMutabaahRapor extends CreateRecord
{
    protected static string $resource = KesantrianMutabaahRaporResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->data;

        $exists = KesantrianMutabaahRapor::where('santri_id', $data['santri_id'])
            ->where('bulan', $data['bulan'])
            ->where('tahun', $data['tahun'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Rapor sudah ada')
                ->body('Rapor mutabaah santri ini untuk bulan dan tahun tersebut sudah pernah dibuat.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $pesantrenId = Auth::user()?->pesantren_id;

        $hasil = KesantrianMutabaahRapor::hitung(
            santriId: $data['santri_id'],
            pesantrenId: $pesantrenId,
            bulan: (int) $data['bulan'],
            tahun: $data['tahun'],
        );

        if ($hasil['total_hari_input'] === 0) {
            Notification::make()
                ->title('Tidak ada data')
                ->body('Tidak ditemukan catatan mutabaah untuk santri ini pada bulan dan tahun yang dipilih.')
                ->warning()
                ->send();

            $this->halt();
        }

        return array_merge($data, $hasil, [
            'pesantren_id' => $pesantrenId,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
