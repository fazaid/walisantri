<?php

namespace App\Enums;

enum PaketLangganan: string
{
    case Gratis    = 'gratis';
    case Rintisan  = 'rintisan';
    case Berkembang = 'berkembang';
    case Maju      = 'maju';

    public function label(): string
    {
        return match($this) {
            self::Gratis     => 'Gratis',
            self::Rintisan   => 'Rintisan',
            self::Berkembang => 'Berkembang',
            self::Maju       => 'Maju',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Gratis     => 'gray',
            self::Rintisan   => 'info',
            self::Berkembang => 'warning',
            self::Maju       => 'success',
        };
    }

    public function maxSantri(): int
    {
        return match($this) {
            self::Gratis     => 10,
            self::Rintisan   => 100,
            self::Berkembang => 500,
            self::Maju       => 1000,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
