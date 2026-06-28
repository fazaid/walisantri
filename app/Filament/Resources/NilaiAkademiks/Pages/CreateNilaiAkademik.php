<?php

namespace App\Filament\Resources\NilaiAkademiks\Pages;

use App\Filament\Resources\NilaiAkademiks\NilaiAkademikResource;
use App\Models\NilaiAkademik;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateNilaiAkademik extends CreateRecord
{
    protected static string $resource = NilaiAkademikResource::class;

    protected function beforeCreate(): void
    {
        $exists = NilaiAkademik::where('santri_id', $this->data['santri_id'])
            ->where('mata_pelajaran_id', $this->data['mata_pelajaran_id'])
            ->where('tahun_ajaran', $this->data['tahun_ajaran'])
            ->where('periode', $this->data['periode'])
            ->where('bulan', $this->data['bulan'] ?? null)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Nilai untuk santri, mata pelajaran, dan periode ini sudah ada.')
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
