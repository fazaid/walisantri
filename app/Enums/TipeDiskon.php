<?php

namespace App\Enums;

enum TipeDiskon: string
{
    case Nominal    = 'nominal';
    case Persentase = 'persentase';

    public function label(): string
    {
        return match($this) {
            self::Nominal    => 'Nominal (Rp)',
            self::Persentase => 'Persentase (%)',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
