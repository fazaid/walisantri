<?php

namespace App\Enums;

enum JenisUangSaku: string
{
    case Setoran     = 'setoran';
    case Pengambilan = 'pengambilan';

    public function label(): string
    {
        return match($this) {
            self::Setoran     => 'Setoran',
            self::Pengambilan => 'Pengambilan',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Setoran     => 'success',
            self::Pengambilan => 'warning',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
