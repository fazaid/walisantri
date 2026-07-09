<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Authorization policy shared by master-data resources restricted to admin_pesantren only.
 */
trait HasAdminOnlyAccess
{
    public static function canViewAny(): bool
    {
        return Auth::user()?->role === UserRole::AdminPesantren->value;
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
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }
}
