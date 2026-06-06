<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Enums\UserRole;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()?->role === UserRole::AdminPesantren->value) {
            $data['pesantren_id'] = auth()->user()->pesantren_id;
        }

        return $data;
    }
}
