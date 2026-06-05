<?php

namespace App\Enums;

enum StatusTagihanSpp: string
{
    case BelumBayar          = 'belum_bayar';
    case MenungguKonfirmasi  = 'menunggu_konfirmasi';
    case Lunas               = 'lunas';

    public function label(): string
    {
        return match($this) {
            self::BelumBayar         => 'Belum Bayar',
            self::MenungguKonfirmasi => 'Menunggu Konfirmasi',
            self::Lunas              => 'Lunas',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::BelumBayar         => 'danger',
            self::MenungguKonfirmasi => 'warning',
            self::Lunas              => 'success',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
