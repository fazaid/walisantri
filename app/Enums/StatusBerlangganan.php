<?php

namespace App\Enums;

enum StatusBerlangganan: string
{
    case Trial     = 'trial';
    case Active    = 'active';
    case Suspended = 'suspended';
    case Expired   = 'expired';

    public function label(): string
    {
        return match($this) {
            self::Trial     => 'Trial',
            self::Active    => 'Aktif',
            self::Suspended => 'Suspended',
            self::Expired   => 'Expired',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Trial     => 'info',
            self::Active    => 'success',
            self::Suspended => 'danger',
            self::Expired   => 'warning',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
