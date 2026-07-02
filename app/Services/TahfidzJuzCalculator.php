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
        $ranges = TahfidzProgress::where('santri_id', $santriId)
            ->whereNotNull('halaman_mulai')
            ->select('halaman_mulai', 'halaman_selesai')
            ->get();

        return ['juz_hafal' => self::juzFromRanges($ranges)];
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
