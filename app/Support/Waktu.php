<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Seluruh waktu DISIMPAN dalam UTC (config/app.php), sementara semua pengguna
 * platform berada di WIB. Akibatnya `now()` mentah tidak boleh dipakai untuk
 * menjawab pertanyaan berbasis KALENDER — antara 00.00–07.00 WIB, `now()`
 * masih menunjuk tanggal kemarin, sehingga input subuh tercatat mundur sehari
 * dan statistik "hari ini" meleset.
 *
 * Aturannya:
 *   - Butuh instan/timestamp (kapan sesuatu terjadi) → tetap pakai `now()`.
 *   - Butuh tanggal/bulan/hari menurut jam dinding pengguna → pakai kelas ini.
 *
 * Nilai yang dikembalikan awalHari()/akhirHari() sudah dikonversi ke UTC
 * supaya aman langsung dibandingkan dengan kolom timestamp di database.
 */
class Waktu
{
    public static function zona(): string
    {
        return config('app.display_timezone');
    }

    /**
     * Waktu sekarang menurut jam dinding pengguna. Kalau hasilnya dipakai di
     * query terhadap kolom timestamp, konversi dulu dengan ->utc().
     */
    public static function sekarang(): Carbon
    {
        return now()->timezone(self::zona());
    }

    /** Tanggal hari ini menurut jam dinding pengguna, format Y-m-d. */
    public static function hariIni(): string
    {
        return self::sekarang()->toDateString();
    }

    /** Batas awal hari WIB, dinyatakan dalam UTC. */
    public static function awalHari(?Carbon $waktu = null): Carbon
    {
        return self::dalamZona($waktu)->startOfDay()->utc();
    }

    /** Batas akhir hari WIB, dinyatakan dalam UTC. */
    public static function akhirHari(?Carbon $waktu = null): Carbon
    {
        return self::dalamZona($waktu)->endOfDay()->utc();
    }

    private static function dalamZona(?Carbon $waktu): Carbon
    {
        return $waktu ? $waktu->copy()->timezone(self::zona()) : self::sekarang();
    }
}
