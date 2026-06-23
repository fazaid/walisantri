<?php

namespace App\Services;

use App\Data\QuranJuz;
use App\Data\QuranSurah;
use App\Models\TahfidzProgress;
use Illuminate\Support\Facades\DB;

class TahfidzJuzCalculator
{
    /**
     * Hitung capaian hafalan santri berbasis batas Juz yang sebenarnya
     * (bukan estimasi rata "total ayat / 6236 * 30"), dari riwayat TahfidzProgress.
     *
     * Asumsi: tiap surah dihafal berurutan dari ayat 1 s.d. ayat_selesai tertinggi
     * yang pernah disetorkan (asumsi yang sama dipakai sejak awal di seluruh portal wali).
     */
    public static function calculate(int $santriId): array
    {
        $maxAyatPerSurah = TahfidzProgress::where('santri_id', $santriId)
            ->select('nama_surah', DB::raw('MAX(ayat_selesai) as max_ayat'))
            ->groupBy('nama_surah')
            ->pluck('max_ayat', 'nama_surah');

        $covered = [];
        foreach ($maxAyatPerSurah as $namaSurah => $maxAyat) {
            $surahNo = QuranSurah::surahNoByName($namaSurah);
            if (! $surahNo) {
                continue;
            }
            $offset = QuranSurah::cumulativeBefore($surahNo);
            for ($ayat = 1; $ayat <= $maxAyat; $ayat++) {
                $covered[$offset + $ayat] = true;
            }
        }

        $totalAyat   = count($covered);
        $juzSelesai  = 0;
        $juzSedang   = null;
        $persenSedang = 0.0;

        foreach (QuranJuz::globalRanges() as $juz => [$start, $end]) {
            $totalDalamJuz = $end - $start + 1;
            $tercoverDalamJuz = 0;
            for ($i = $start; $i <= $end; $i++) {
                if (! empty($covered[$i])) {
                    $tercoverDalamJuz++;
                }
            }

            $persen = $totalDalamJuz > 0 ? ($tercoverDalamJuz / $totalDalamJuz * 100) : 0;

            if ($persen >= 100) {
                $juzSelesai++;
                continue;
            }

            if ($persen > 0) {
                $juzSedang    = $juz;
                $persenSedang = round($persen, 1);
            }

            // Juz pertama yang belum 100% selesai — berhenti di sini, karena hafalan
            // diasumsikan berurutan per juz (juz setelahnya pasti belum disentuh).
            break;
        }

        return [
            'juz_selesai'      => $juzSelesai,
            'juz_sedang'       => $juzSedang,
            'persen_sedang'    => $persenSedang,
            'total_ayat_hafal' => $totalAyat,
        ];
    }
}
