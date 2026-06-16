<?php

namespace App\Enums;

enum PaketLangganan: string
{
    case Rintisan   = 'rintisan';
    case Tumbuh     = 'tumbuh';
    case Berkembang = 'berkembang';
    case Maju       = 'maju';

    public function label(): string
    {
        return match($this) {
            self::Rintisan   => 'Rintisan',
            self::Tumbuh     => 'Tumbuh',
            self::Berkembang => 'Berkembang',
            self::Maju       => 'Maju',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Rintisan   => 'info',
            self::Tumbuh     => 'success',
            self::Berkembang => 'warning',
            self::Maju       => 'primary',
        };
    }

    public function maxSantri(): int
    {
        return match($this) {
            self::Rintisan   => 100,
            self::Tumbuh     => 250,
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
