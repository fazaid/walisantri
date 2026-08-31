<?php

namespace App\Services;

use App\Support\Waktu;

class TrendBulanan
{
    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function duaBelasBulanTerakhir(): array
    {
        $bulan = [];

        // ⚠️ startOfMonth() SEBELUM subMonths(), bukan sesudah — dan di sini
        // akibatnya tidak sekadar meleset sehari.
        //
        // Carbon meluap saat tanggal acuan tidak ada di bulan tujuan. Dipanggil
        // pada 31 Agustus 2026, versi tanpa jangkar mengembalikan:
        //   2025-10 2025-10 2025-12 2025-12 2026-01 2026-03 2026-03
        //   2026-05 2026-05 2026-07 2026-07 2026-08
        // — 12 entri tapi hanya 7 bulan unik: lima bulan tercetak dua kali, dan
        // September, November, Februari, April, serta Juni hilang sama sekali.
        // Grafik tren di dasbor jadi berbohong tanpa satu pun error, dan hanya
        // pada tanggal-tanggal tertentu sehingga sulit ditangkap dari laporan.
        $awal = Waktu::sekarang()->startOfMonth();

        for ($i = 11; $i >= 0; $i--) {
            $tanggal = $awal->copy()->subMonths($i);
            $bulan[] = ['key' => $tanggal->format('Y-m'), 'label' => $tanggal->translatedFormat('M Y')];
        }

        return $bulan;
    }
}
