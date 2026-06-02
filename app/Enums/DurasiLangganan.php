<?php

namespace App\Enums;

enum DurasiLangganan: int
{
    case SatuBulan      = 1;
    case TigaBulan      = 3;
    case EnamBulan      = 6;
    case DuabelasBulan  = 12;

    public function label(): string
    {
        return match($this) {
            self::SatuBulan     => '1 Bulan',
            self::TigaBulan     => '3 Bulan',
            self::EnamBulan     => '6 Bulan',
            self::DuabelasBulan => '12 Bulan (1 Tahun)',
        };
    }

    public function bonusBulan(): int
    {
        return match($this) {
            self::DuabelasBulan => (int) config('billing.diskon_tahunan.bonus_bulan', 2),
            default             => 0,
        };
    }

    // Bulan yang dibayar = durasi yang dipilih - bonus gratis
    public function bulanBayar(): int
    {
        return $this->value - $this->bonusBulan();
    }

    // Total aktif = value (yang dipilih sudah merupakan total aktif)
    public function totalBulan(): int
    {
        return $this->value;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
