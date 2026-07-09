<?php

namespace App\Services;

class TahunAjaranOptions
{
    public static function current(): string
    {
        return now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;
    }

    public static function currentPeriode(): string
    {
        return now()->month >= 7 ? 'Semester_Ganjil' : 'Semester_Genap';
    }

    public static function options(int $before = 2, int $after = 1): array
    {
        $startYear = now()->month >= 7 ? now()->year : now()->year - 1;

        $options = [];
        for ($i = -$before; $i <= $after; $i++) {
            $year = $startYear + $i;
            $label = $year.'/'.($year + 1);
            $options[$label] = $label;
        }

        return $options;
    }

    public static function periodeOptions(): array
    {
        return [
            'Bulanan' => 'Bulanan',
            'Semester_Ganjil' => 'Semester Ganjil',
            'Semester_Genap' => 'Semester Genap',
        ];
    }

    public static function bulanOptions(?string $tahunAjaran): array
    {
        if (! $tahunAjaran) {
            return [];
        }

        [$startYear, $endYear] = array_map('intval', explode('/', $tahunAjaran));

        $nama = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $options = [];
        for ($m = 7; $m <= 12; $m++) {
            $options["{$m}-{$startYear}"] = $nama[$m].' '.$startYear;
        }
        for ($m = 1; $m <= 6; $m++) {
            $options["{$m}-{$endYear}"] = $nama[$m].' '.$endYear;
        }

        return $options;
    }
}
