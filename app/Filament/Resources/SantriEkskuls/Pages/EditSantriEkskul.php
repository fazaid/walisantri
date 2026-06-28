<?php

namespace App\Filament\Resources\SantriEkskuls\Pages;

use App\Filament\Resources\SantriEkskuls\SantriEkskulResource;
use App\Models\SantriEkskul;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSantriEkskul extends EditRecord
{
    protected static string $resource = SantriEkskulResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function beforeSave(): void
    {
        $exists = SantriEkskul::where('santri_id', $this->data['santri_id'])
            ->where('ekskul_id', $this->data['ekskul_id'])
            ->where('id', '!=', $this->record->id)
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Santri ini sudah terdaftar di ekskul yang dipilih.')
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
