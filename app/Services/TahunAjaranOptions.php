<?php

namespace App\Services;

use App\Support\Waktu;
use Illuminate\Support\Carbon;

class TahunAjaranOptions
{
    public static function current(): string
    {
        return Waktu::sekarang()->month >= 7
            ? Waktu::sekarang()->year.'/'.(Waktu::sekarang()->year + 1)
            : (Waktu::sekarang()->year - 1).'/'.Waktu::sekarang()->year;
    }

    public static function currentPeriode(): string
    {
        return Waktu::sekarang()->month >= 7 ? 'Semester_Ganjil' : 'Semester_Genap';
    }

    public static function options(int $before = 2, int $after = 1): array
    {
        $startYear = Waktu::sekarang()->month >= 7 ? Waktu::sekarang()->year : Waktu::sekarang()->year - 1;

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

    /**
     * Rentang tanggal [awal, akhir] untuk kombinasi tahun ajaran + periode.
     *
     * Dipakai modul rapor yang datanya difilter per tanggal (setoran tahfidz,
     * mutabaah harian), bukan per kolom periode. Kolom sasarannya bertipe DATE
     * (tanpa jam), jadi batas sengaja TIDAK dikonversi ke UTC lewat Waktu —
     * konversi justru menggeser batas 7 jam dan menarik masuk satu hari ekstra.
     */
    public static function rentangTanggal(string $tahunAjaran, string $periode, ?string $bulan = null): array
    {
        [$startYear, $endYear] = array_map('intval', explode('/', $tahunAjaran));

        if ($periode === 'Bulanan' && $bulan) {
            [$month, $year] = array_map('intval', explode('-', $bulan));
            $awal = Carbon::create($year, $month, 1)->startOfDay();

            return [$awal, $awal->copy()->endOfMonth()->endOfDay()];
        }

        return match ($periode) {
            'Semester_Ganjil' => [Carbon::create($startYear, 7, 1)->startOfDay(), Carbon::create($startYear, 12, 31)->endOfDay()],
            'Semester_Genap' => [Carbon::create($endYear, 1, 1)->startOfDay(), Carbon::create($endYear, 6, 30)->endOfDay()],
            default => [Carbon::create($startYear, 7, 1)->startOfDay(), Carbon::create($endYear, 6, 30)->endOfDay()],
        };
    }

    /** Label periode siap tampil, mis. "Semester Ganjil" atau "November 2026". */
    public static function labelPeriode(string $periode, ?string $bulan = null, ?string $tahunAjaran = null): string
    {
        if ($periode === 'Bulanan' && $bulan) {
            return self::bulanOptions($tahunAjaran)[$bulan] ?? $bulan;
        }

        return self::periodeOptions()[$periode] ?? str_replace('_', ' ', $periode);
    }
}
