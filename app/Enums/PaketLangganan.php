<?php

namespace App\Enums;

enum PaketLangganan: string
{
    case Rintisan  = 'rintisan';
    case Berkembang = 'berkembang';
    case Akselerasi = 'akselerasi';
    case Besar     = 'besar';

    public function label(): string
    {
        return match($this) {
            self::Rintisan   => 'Rintisan',
            self::Berkembang => 'Berkembang',
            self::Akselerasi => 'Akselerasi',
            self::Besar      => 'Besar',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Rintisan   => 'gray',
            self::Berkembang => 'info',
            self::Akselerasi => 'warning',
            self::Besar      => 'success',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
