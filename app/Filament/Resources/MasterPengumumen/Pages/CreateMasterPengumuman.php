<?php

namespace App\Filament\Resources\MasterPengumumen\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\MasterPengumumen\MasterPengumumanResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMasterPengumuman extends CreateRecord
{
    protected static string $resource = MasterPengumumanResource::class;

    // Super Admin menyimpan dengan pesantren_id = null (pengumuman global).
    // Admin/Ustadz menyimpan dengan pesantren_id miliknya sendiri.
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['pesantren_id'] = $user->role === UserRole::SuperAdmin->value
            ? null
            : $user->pesantren_id;

        return $data;
    }
}
