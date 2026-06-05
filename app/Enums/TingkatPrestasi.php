<?php

namespace App\Enums;

enum TingkatPrestasi: string
{
    case Internal       = 'internal';
    case Kabupaten      = 'kabupaten';
    case Provinsi       = 'provinsi';
    case Nasional       = 'nasional';
    case Internasional  = 'internasional';

    public function label(): string
    {
        return match($this) {
            self::Internal      => 'Internal Pesantren',
            self::Kabupaten     => 'Kabupaten / Kota',
            self::Provinsi      => 'Provinsi',
            self::Nasional      => 'Nasional',
            self::Internasional => 'Internasional',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Internal      => 'gray',
            self::Kabupaten     => 'info',
            self::Provinsi      => 'warning',
            self::Nasional      => 'success',
            self::Internasional => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
