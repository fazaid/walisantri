<?php

namespace App\Services\Rapor;

use App\Models\NilaiAkademik;
use App\Models\SantriEkskul;
use Illuminate\Support\Collection;

class RaporAkademikData
{
    /**
     * @return array{ada_data: bool, nilai: Collection, ekskul: Collection, rata_rata: ?float}
     */
    public static function untuk(int $santriId, string $tahunAjaran, string $periode, ?string $bulan = null): array
    {
        $nilai = NilaiAkademik::with('mataPelajaran')
            ->where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('periode', $periode)
            ->when($periode === 'Bulanan' && $bulan, fn ($q) => $q->where('bulan', $bulan))
            ->get();

        $ekskul = SantriEkskul::with('ekskulMaster')
            ->where('santri_id', $santriId)
            ->where('aktif', true)
            ->orderBy('tanggal_mulai')
            ->get();

        return [
            'ada_data' => $nilai->isNotEmpty(),
            'nilai' => $nilai,
            'ekskul' => $ekskul,
            'rata_rata' => $nilai->isNotEmpty() ? round((float) $nilai->avg('nilai'), 1) : null,
        ];
    }
}
