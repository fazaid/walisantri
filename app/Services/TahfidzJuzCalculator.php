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

        $covered = [];
        foreach ($ranges as $r) {
            for ($p = $r->halaman_mulai; $p <= $r->halaman_selesai; $p++) {
                $covered[$p] = true;
            }
        }

        return [
            'juz_hafal' => round(min(count($covered) / 20, 30), 2),
        ];
    }
}
