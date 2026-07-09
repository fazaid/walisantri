<?php

namespace App\Filament\Resources\Santris\Pages;

use App\Exceptions\SantriQuotaExceededException;
use App\Filament\Resources\Santris\SantriResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSantri extends CreateRecord
{
    protected static string $resource = SantriResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return static::getModel()::create($data);
        } catch (SantriQuotaExceededException $e) {
            Notification::make()
                ->title('Kuota Santri Penuh')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
