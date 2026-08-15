<?php

namespace App\Enums;

enum JenisIzin: string
{
    case Sakit = 'sakit';
    case Izin = 'izin';
    case Pulang = 'pulang';
    case Dispensasi = 'dispensasi';

    public function label(): string
    {
        return match ($this) {
            self::Sakit => 'Sakit',
            self::Izin => 'Izin',
            self::Pulang => 'Pulang (Bermalam)',
            self::Dispensasi => 'Dispensasi (Tugas/Lomba)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Sakit => 'danger',
            self::Izin => 'warning',
            self::Pulang => 'info',
            self::Dispensasi => 'info',
        };
    }

    /**
     * Status kehadiran yang ditulis saat izin ini disetujui.
     *
     * Satu titik pemetaan izin→kehadiran. Ditaruh di enum, bukan di service,
     * supaya form, service, dan rekap tidak bisa diam-diam punya versi
     * pemetaan masing-masing.
     */
    public function keStatusKehadiran(): StatusKehadiran
    {
        return match ($this) {
            self::Sakit => StatusKehadiran::Sakit,
            self::Izin => StatusKehadiran::Izin,
            self::Pulang => StatusKehadiran::Pulang,
            self::Dispensasi => StatusKehadiran::Dispensasi,
        };
    }

    /**
     * Nilai status_udzur di kesantrian_mutabaah yang setara.
     *
     * Modul Mutaba'ah punya kosakata udzurnya sendiri yang lebih sempit
     * (Tidak/Sakit/Haid/Izin_Pulang/Tugas_Pondok), jadi Izin dan Pulang
     * sama-sama jatuh ke Izin_Pulang.
     */
    public function keStatusUdzur(): string
    {
        return match ($this) {
            self::Sakit => 'Sakit',
            self::Izin, self::Pulang => 'Izin_Pulang',
            self::Dispensasi => 'Tugas_Pondok',
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
