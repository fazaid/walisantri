<?php

namespace App\Filament\Resources\KesantrianKarakterRapors\Pages;

use App\Filament\Resources\KesantrianKarakterRapors\KesantrianKarakterRaporResource;
use App\Models\KesantrianKarakterRapor;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditKesantrianKarakterRapor extends EditRecord
{
    protected static string $resource = KesantrianKarakterRaporResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $data = $this->data;

        $exists = KesantrianKarakterRapor::where('santri_id', $data['santri_id'])
            ->where('tahun_ajaran', $data['tahun_ajaran'])
            ->where('periode', $data['periode'])
            ->where('bulan', $data['bulan'] ?? null)
            ->where('id', '!=', $this->record->id)
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
