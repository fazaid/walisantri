<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Authorization policy shared by resources where admin_pesantren and ustadz
 * may view/create/edit, but only admin_pesantren may delete.
 */
trait HasAdminUstadzAccess
{
    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ]);
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->role === UserRole::AdminPesantren->value;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->role === UserRole::AdminPesantren->value;
    }
}
