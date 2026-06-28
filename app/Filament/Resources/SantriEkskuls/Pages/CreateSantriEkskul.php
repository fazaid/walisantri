<?php

namespace App\Filament\Resources\SantriEkskuls\Pages;

use App\Filament\Resources\SantriEkskuls\SantriEkskulResource;
use App\Models\SantriEkskul;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSantriEkskul extends CreateRecord
{
    protected static string $resource = SantriEkskulResource::class;

    protected function beforeCreate(): void
    {
        $exists = SantriEkskul::where('santri_id', $this->data['santri_id'])
            ->where('ekskul_id', $this->data['ekskul_id'])
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
