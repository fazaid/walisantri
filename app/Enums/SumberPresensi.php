<?php

namespace App\Enums;

/**
 * Dari mana baris presensi berasal.
 *
 * Bukan sekadar informasi: `izin` dipakai Fase 4 untuk membedakan baris yang
 * dibuat otomatis oleh persetujuan izin dari baris yang sejak itu disunting
 * manusia. Pembatalan izin hanya boleh menghapus yang pertama — koreksi manual
 * ustadz tidak boleh ikut hilang.
 */
enum SumberPresensi: string
{
    case Manual = 'manual';
    case Qr = 'qr';
    case Izin = 'izin';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Qr => 'Scan QR',
            self::Izin => 'Dari Izin',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Manual => 'gray',
            self::Qr => 'success',
            self::Izin => 'info',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
