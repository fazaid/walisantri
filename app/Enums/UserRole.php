<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case AdminPesantren = 'admin_pesantren';
    case Ustadz = 'ustadz';
    case WaliSantri = 'wali_santri';

    public function label(): string
    {
        return match($this) {
            self::SuperAdmin    => 'Super Admin',
            self::AdminPesantren => 'Admin Pesantren',
            self::Ustadz        => 'Ustadz',
            self::WaliSantri    => 'Wali Santri',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::SuperAdmin     => 'danger',
            self::AdminPesantren => 'warning',
            self::Ustadz         => 'success',
            self::WaliSantri     => 'info',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
