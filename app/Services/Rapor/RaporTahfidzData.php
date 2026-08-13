<?php

namespace App\Services\Rapor;

use App\Models\TahfidzProgress;
use App\Models\TahfidzUjian;
use App\Services\TahfidzJuzCalculator;
use App\Services\TahunAjaranOptions;
use Illuminate\Support\Collection;

class RaporTahfidzData
{
    /**
     * @return array{ada_data: bool, ujian: Collection, setoran_stats: array, total_juz_lulus: int}
     */
    public static function untuk(int $santriId, string $tahunAjaran, string $periode, ?string $bulan = null): array
    {
        $ujian = TahfidzUjian::with('penguji')
            ->where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('periode', $periode)
            ->when($periode === 'Bulanan' && $bulan, fn ($q) => $q->where('bulan', $bulan))
            ->orderByDesc('tanggal_ujian')
            ->get();

        // Setoran harian tidak punya kolom periode, jadi disaring lewat rentang tanggal.
        [$awal, $akhir] = TahunAjaranOptions::rentangTanggal($tahunAjaran, $periode, $bulan);

        $setoran = TahfidzProgress::where('santri_id', $santriId)
            ->whereBetween('tanggal', [$awal, $akhir])
            ->orderBy('tanggal')
            ->get();

        return [
            'ada_data' => $ujian->isNotEmpty() || $setoran->isNotEmpty(),
            'ujian' => $ujian,
            'setoran_stats' => self::statistikSetoran($setoran),
            'total_juz_lulus' => (int) (TahfidzUjian::where('santri_id', $santriId)
                ->where('status_kelulusan', 'Lulus')
                ->max('target_juz') ?? 0),
        ];
    }

    private static function statistikSetoran(Collection $list): array
    {
        if ($list->isEmpty()) {
            return [
                'total_setoran' => 0,
                'total_juz' => 0,
                'hari_aktif' => 0,
                'per_tipe' => collect(),
                'nilai_distribusi' => collect(),
                'surah_list' => collect(),
            ];
        }

        $jumlahHalaman = fn ($p) => ($p->halaman_mulai && $p->halaman_selesai)
            ? $p->halaman_selesai - $p->halaman_mulai + 1
            : 0;

        return [
            'total_setoran' => $list->count(),
            'total_juz' => TahfidzJuzCalculator::juzFromRanges($list),
            'hari_aktif' => $list->pluck('tanggal')->map(fn ($d) => $d->toDateString())->unique()->count(),
            'per_tipe' => $list->groupBy('tipe_setoran')->map(fn ($g) => [
                'jumlah' => $g->count(),
                'halaman' => $g->sum($jumlahHalaman),
            ]),
            'nilai_distribusi' => $list->groupBy('nilai_kelancaran')->map->count(),
            'surah_list' => $list->pluck('nama_surah')->filter()->unique()->values(),
        ];
    }
}
