<?php

namespace App\Services\Rapor;

use App\Models\KesantrianKarakterRapor;

class RaporKarakterData
{
    /** @return array{ada_data: bool, rapor: ?KesantrianKarakterRapor, adab_fields: array, kepribadian_fields: array} */
    public static function untuk(int $santriId, string $tahunAjaran, string $periode, ?string $bulan = null): array
    {
        $query = KesantrianKarakterRapor::where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('periode', $periode);

        if ($periode === 'Bulanan') {
            $query->where('bulan', $bulan);
        } else {
            $query->whereNull('bulan');
        }

        $rapor = $query->latest('tanggal_input')->first();

        return [
            'ada_data' => (bool) $rapor,
            'rapor' => $rapor,
            'adab_fields' => self::adabFields(),
            'kepribadian_fields' => self::kepribadianFields(),
        ];
    }

    public static function adabFields(): array
    {
        return [
            'adab_ustadz' => 'Adab ke Ustadz',
            'adab_tamu' => 'Adab ke Tamu',
            'adab_asrama' => 'Adab Asrama',
            'adab_kelas' => 'Adab Kelas',
            'adab_sholat' => 'Adab Sholat',
            'adab_quran' => 'Adab Al-Quran',
            'adab_minum' => 'Adab Minum',
        ];
    }

    public static function kepribadianFields(): array
    {
        return [
            'kepribadian_tanggungjawab' => 'Tanggung Jawab',
            'kepribadian_kemandirian' => 'Kemandirian',
            'kepribadian_kepatuhan' => 'Kepatuhan',
            'kepribadian_kebersihan' => 'Kebersihan',
            'kepribadian_mengelola' => 'Mengelola Diri',
            'kepribadian_kepedulian' => 'Kepedulian',
            'kepribadian_empati' => 'Empati',
            'kepribadian_kebersamaan' => 'Kebersamaan',
            'kepribadian_kedisiplinan' => 'Kedisiplinan',
        ];
    }
}
