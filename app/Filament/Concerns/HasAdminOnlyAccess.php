<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use App\Filament\Support\ModulKomponen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Authorization policy shared by master-data resources restricted to admin_pesantren only.
 *
 * ⚠️ Trait ini juga dipakai KelasResource dan KamarResource — keduanya inti sistem.
 * Pemeriksaan modul di bawah aman untuk mereka karena modulnya diturunkan dari
 * cluster, dan Cluster Santri tidak punya modul (ModulKomponen mengembalikan null).
 * Kalau pemetaan itu suatu saat diubah jadi per-kelas, keduanya bisa ikut hilang
 * tanpa galat apa pun — ModulNavigasiTest yang menjaganya.
 */
trait HasAdminOnlyAccess
{
    public static function canViewAny(): bool
    {
        // Cek peran didahulukan: ia gratis, sehingga super_admin dan wali santri
        // tidak pernah memicu lookup pengaturan modul sama sekali.
        return Auth::user()?->role === UserRole::AdminPesantren->value
            && ModulKomponen::aktif(static::class);
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
