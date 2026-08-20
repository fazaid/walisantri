<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use App\Filament\Support\ModulKomponen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Authorization policy shared by resources where admin_pesantren and ustadz
 * may view/create/edit, but only admin_pesantren may delete.
 *
 * Sejak v4.57 setiap pemeriksaan juga menanyakan apakah modul pemilik resource ini
 * masih dinyalakan pesantrennya. Modulnya DITURUNKAN DARI CLUSTER (ModulKomponen),
 * bukan ditulis per kelas — itulah yang membuat trait ini aman dipakai KelasResource
 * dan KamarResource, yang ada di Cluster Santri dan karena itu tidak punya modul
 * sama sekali: jawabannya null, dan gate-nya tidak berubah sedikit pun.
 */
trait HasAdminUstadzAccess
{
    public static function canViewAny(): bool
    {
        // Cek peran didahulukan: ia gratis, sehingga super_admin dan wali santri
        // tidak pernah memicu lookup pengaturan modul sama sekali.
        return in_array(Auth::user()?->role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ]) && ModulKomponen::aktif(static::class);
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
        return Auth::user()?->role === UserRole::AdminPesantren->value
            && ModulKomponen::aktif(static::class);
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->role === UserRole::AdminPesantren->value
            && ModulKomponen::aktif(static::class);
    }
}
