<?php

namespace App\Services\Rapor;

use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use App\Services\TahunAjaranOptions;
use Illuminate\Support\Collection;

class RaporMutabaahData
{
    /**
     * @return array{ada_data: bool, total_hari: int, total_udzur: int, udzur_detail: array, amalan: array, rata_rata: int}
     */
    public static function untuk(int $santriId, string $tahunAjaran, string $periode, ?string $bulan = null): array
    {
        // Mutabaah dicatat per hari tanpa kolom periode, jadi periode rapor
        // diterjemahkan dulu jadi rentang tanggal.
        [$awal, $akhir] = TahunAjaranOptions::rentangTanggal($tahunAjaran, $periode, $bulan);

        $records = KesantrianMutabaah::where('santri_id', $santriId)
            ->whereBetween('tanggal', [$awal, $akhir])
            ->orderBy('tanggal')
            ->get();

        if ($records->isEmpty()) {
            return [
                'ada_data' => false,
                'total_hari' => 0,
                'total_udzur' => 0,
                'udzur_detail' => [],
                'amalan' => [],
                'rata_rata' => 0,
            ];
        }

        $masters = KesantrianAmalMaster::where('aktif', true)->orderBy('urutan')->get();

        $totalHari = $records->count();
        $amalan = self::rekapAmalan($records, $masters, $totalHari);

        return [
            'ada_data' => true,
            'total_hari' => $totalHari,
            'total_udzur' => $records->where('status_udzur', '!=', 'Tidak')->count(),
            'udzur_detail' => $records->where('status_udzur', '!=', 'Tidak')
                ->groupBy('status_udzur')
                ->map->count()
                ->toArray(),
            'amalan' => $amalan,
            'rata_rata' => count($amalan) > 0
                ? (int) round(array_sum(array_column($amalan, 'persen')) / count($amalan))
                : 0,
        ];
    }

    private static function rekapAmalan(Collection $records, Collection $masters, int $totalHari): array
    {
        $amalan = [];

        foreach ($masters as $master) {
            $kode = $master->kode;

            // ⚠️ Ikon dikirim TERPISAH, tidak lagi ditempelkan ke label. Ikonnya emoji
            // (AmalanDefault: 🕌 🌙 🌃 …), dan PDF rapor dirender DomPDF dengan DejaVu
            // Sans — font itu tidak punya satu pun glif emoji, jadi yang tercetak
            // adalah kotak kosong plus spasi menggantung di depan tiap nama amalan.
            // Layar tetap menampilkannya (browser punya font emoji); PDF mengabaikannya.
            $label = $master->label;

            if ($master->tipe === 'hitungan') {
                $total = (int) $records->sum(fn ($r) => $r->amalan[$kode] ?? 0);
                $maks = $totalHari * $master->nilai_maks;

                $amalan[] = [
                    'label' => $label,
                    'icon' => $master->icon,
                    'tipe' => 'hitungan',
                    'nilai_maks' => $master->nilai_maks,
                    'total_capai' => $total,
                    'total_maks' => $maks,
                    'persen' => $maks > 0 ? (int) round($total / $maks * 100) : 0,
                ];

                continue;
            }

            $total = $records->filter(fn ($r) => ! empty($r->amalan[$kode]))->count();

            $amalan[] = [
                'label' => $label,
                'icon' => $master->icon,
                'tipe' => 'boolean',
                'total_capai' => $total,
                'total_maks' => $totalHari,
                'persen' => $totalHari > 0 ? (int) round($total / $totalHari * 100) : 0,
            ];
        }

        return $amalan;
    }
}
