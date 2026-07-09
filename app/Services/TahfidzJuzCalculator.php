<?php

namespace App\Services;

use App\Models\TahfidzProgress;

class TahfidzJuzCalculator
{
    /**
     * Hitung capaian hafalan santri dalam satuan juz (0–30).
     *
     * Setiap setoran disimpan sebagai range halaman mushaf (halaman_mulai–halaman_selesai).
     * Total halaman unik yang tercakup dibagi 20 (20 halaman per juz).
     */
    public static function calculate(int $santriId): array
    {
        return self::calculateMany([$santriId])[$santriId];
    }

    /**
     * Versi batch untuk banyak santri sekaligus — satu query untuk semua,
     * menghindari N+1 saat dipanggil dalam loop (mis. widget/dashboard).
     *
     * @param  array<int>  $santriIds
     * @return array<int, array{juz_hafal: float}>
     */
    public static function calculateMany(array $santriIds): array
    {
        $rangesBySantri = TahfidzProgress::whereIn('santri_id', $santriIds)
            ->whereNotNull('halaman_mulai')
            ->select('santri_id', 'halaman_mulai', 'halaman_selesai')
            ->get()
            ->groupBy('santri_id');

        $result = [];
        foreach ($santriIds as $santriId) {
            $result[$santriId] = ['juz_hafal' => self::juzFromRanges($rangesBySantri->get($santriId, collect()))];
        }

        return $result;
    }

    /**
     * Konversi kumpulan setoran (mis. sudah difilter ke periode tertentu) ke juz —
     * dedup halaman unik yang tercakup dulu (overlap antar setoran tidak dobel-hitung).
     */
    public static function juzFromRanges(iterable $ranges): float
    {
        $covered = [];
        foreach ($ranges as $r) {
            if (! $r->halaman_mulai || ! $r->halaman_selesai) {
                continue;
            }
            for ($p = $r->halaman_mulai; $p <= $r->halaman_selesai; $p++) {
                $covered[$p] = true;
            }
        }

        return round(min(count($covered) / 20, 30), 2);
    }
}
