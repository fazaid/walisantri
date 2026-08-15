<?php

namespace App\Enums;

enum StatusKehadiran: string
{
    case Hadir = 'Hadir';
    case Sakit = 'Sakit';
    case Izin = 'Izin';
    case Alpa = 'Alpa';
    case Terlambat = 'Terlambat';
    case Pulang = 'Pulang';
    case Dispensasi = 'Dispensasi';

    public function label(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
            self::Sakit => 'Sakit',
            self::Izin => 'Izin',
            self::Alpa => 'Alpa',
            self::Terlambat => 'Terlambat',
            self::Pulang => 'Pulang (Bermalam)',
            self::Dispensasi => 'Dispensasi',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Hadir => 'success',
            self::Terlambat => 'warning',
            self::Izin => 'warning',
            self::Sakit => 'danger',
            self::Alpa => 'danger',
            self::Pulang => 'info',
            self::Dispensasi => 'info',
        };
    }

    /**
     * Apakah status ini dihitung sebagai hadir untuk persentase kehadiran?
     *
     * Definisi "% kehadiran" ditaruh di sini sejak awal — sebelum rekap, ekspor
     * Excel, dan PDF rapor sempat masing-masing membuat versinya sendiri lalu
     * menyimpang diam-diam (persis yang terjadi pada halaman rapor wali di v4.19).
     *
     * Terlambat dan Dispensasi ikut dihitung hadir: santrinya ada di tempat.
     * Yang membedakan Terlambat hanyalah jamnya, dan Dispensasi justru berarti
     * santri sedang menjalankan tugas pondok.
     */
    public function hadirEfektif(): bool
    {
        return in_array($this, [self::Hadir, self::Terlambat, self::Dispensasi], true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
