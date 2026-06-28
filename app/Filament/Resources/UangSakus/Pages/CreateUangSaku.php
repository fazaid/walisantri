<?php

namespace App\Filament\Resources\UangSakus\Pages;

use App\Filament\Resources\UangSakus\UangSakuResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUangSaku extends CreateRecord
{
    protected static string $resource = UangSakuResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['dicatat_oleh'] = auth()->id();
        return $data;
    }
}
