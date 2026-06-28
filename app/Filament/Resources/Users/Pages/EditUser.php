<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (auth()->user()?->role === UserRole::AdminPesantren->value) {
            $data['pesantren_id'] = auth()->user()->pesantren_id;

            if (isset($data['role']) && $data['role'] === UserRole::SuperAdmin->value) {
                $data['role'] = $this->record->role;
            }
        }

        return $data;
    }
}
