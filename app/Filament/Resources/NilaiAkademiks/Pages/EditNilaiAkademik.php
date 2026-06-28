<?php

namespace App\Filament\Resources\NilaiAkademiks\Pages;

use App\Filament\Resources\NilaiAkademiks\NilaiAkademikResource;
use App\Models\NilaiAkademik;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditNilaiAkademik extends EditRecord
{
    protected static string $resource = NilaiAkademikResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function beforeSave(): void
    {
        $exists = NilaiAkademik::where('santri_id', $this->data['santri_id'])
            ->where('mata_pelajaran_id', $this->data['mata_pelajaran_id'])
            ->where('tahun_ajaran', $this->data['tahun_ajaran'])
            ->where('periode', $this->data['periode'])
            ->where('bulan', $this->data['bulan'] ?? null)
            ->where('id', '!=', $this->record->id)
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
