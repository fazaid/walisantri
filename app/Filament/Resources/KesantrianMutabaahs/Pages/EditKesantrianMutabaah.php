<?php

namespace App\Filament\Resources\KesantrianMutabaahs\Pages;

use App\Filament\Resources\KesantrianMutabaahs\KesantrianMutabaahResource;
use App\Models\KesantrianMutabaah;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditKesantrianMutabaah extends EditRecord
{
    protected static string $resource = KesantrianMutabaahResource::class;

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

        $bentrok = KesantrianMutabaah::where('santri_id', $data['santri_id'])
            ->where('tanggal', $data['tanggal'])
            ->where('id', '!=', $this->record->id)
            ->exists();

        if ($bentrok) {
            Notification::make()
                ->title('Tanggal bentrok')
                ->body('Sudah ada mutaba\'ah untuk santri dan tanggal tersebut. Ubah tanggal atau santri terlebih dahulu.')
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
