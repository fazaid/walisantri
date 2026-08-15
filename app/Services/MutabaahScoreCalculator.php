<?php

namespace App\Services;

use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MutabaahScoreCalculator
{
    /** @var array<int, Collection<int, KesantrianAmalMaster>> */
    protected static array $masterCache = [];

    public static function masterAktif(int $pesantrenId): Collection
    {
        return self::$masterCache[$pesantrenId] ??= KesantrianAmalMaster::where('pesantren_id', $pesantrenId)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();
    }

    public static function maxScore(int $pesantrenId): int
    {
        return (int) self::masterAktif($pesantrenId)->sum('bobot');
    }

    public static function skor(KesantrianMutabaah $mutabaah): int
    {
        $amalan = $mutabaah->amalan ?? [];
        $skor = 0.0;

        foreach (self::masterAktif($mutabaah->pesantren_id) as $item) {
            $nilai = $amalan[$item->kode] ?? null;

            $skor += $item->tipe === 'hitungan'
                ? (min((float) ($nilai ?? 0), $item->nilai_maks ?: 1) / max($item->nilai_maks, 1)) * $item->bobot
                : ($nilai ? $item->bobot : 0);
        }

        return (int) round($skor);
    }

    public static function persentase(KesantrianMutabaah $mutabaah): int
    {
        $max = self::maxScore($mutabaah->pesantren_id);

        return $max > 0 ? (int) round(self::skor($mutabaah) / $max * 100) : 0;
    }

    /** @param Collection<int, KesantrianMutabaah> $list */
    public static function persentaseRataRata(Collection $list): int
    {
        if ($list->isEmpty()) {
            return 0;
        }

        $totalSkor = 0;
        $totalMaks = 0;

        foreach ($list->groupBy('pesantren_id') as $pesantrenId => $group) {
            $max = self::maxScore((int) $pesantrenId);
            $totalSkor += $group->sum(fn (KesantrianMutabaah $m) => self::skor($m));
            $totalMaks += $max * $group->count();
        }

        return $totalMaks > 0 ? (int) round($totalSkor / $totalMaks * 100) : 0;
    }

    /**
     * Breakdown konsistensi per amal untuk satu santri (atau sekelompok record dari satu pesantren yang sama),
     * dipakai di halaman statistik wali sebagai pengganti daftar amal yang dulu hardcoded.
     *
     * @param  Collection<int, KesantrianMutabaah>  $list
     * @return array<int, array{kode: string, tipe: string, icon: string, label: string, total: int|float, max: int, unit: string, pct: int}>
     */
    public static function breakdown(Collection $list, int $pesantrenId): array
    {
        $totalHari = $list->count();

        return self::masterAktif($pesantrenId)->map(function (KesantrianAmalMaster $item) use ($list, $totalHari) {
            if ($item->tipe === 'hitungan') {
                $total = $list->sum(fn (KesantrianMutabaah $m) => (float) (($m->amalan ?? [])[$item->kode] ?? 0));
                $max = $totalHari * $item->nilai_maks;
            } else {
                $total = $list->filter(fn (KesantrianMutabaah $m) => (bool) (($m->amalan ?? [])[$item->kode] ?? false))->count();
                $max = $totalHari;
            }

            return [
                'kode' => $item->kode,
                'tipe' => $item->tipe,
                'icon' => $item->icon ?: '✅',
                'label' => $item->label,
                'total' => $total,
                'max' => $max,
                'unit' => $item->satuan,
                'pct' => $max > 0 ? (int) round($total / $max * 100) : 0,
            ];
        })->values()->all();
    }

    /**
     * Versi streaming dari persentaseRataRata() + breakdown() untuk rentang yang
     * tidak dibatasi tanggal.
     *
     * Halaman statistik wali menampilkan angka "dari seluruh waktu tercatat", jadi
     * agregatnya memang harus menyentuh seluruh riwayat santri — tapi jalur lama
     * melakukannya dengan ->get() sehingga tiap baris dihidupkan jadi model Eloquent
     * lengkap dengan jsonb-nya. Untuk santri yang sudah bertahun-tahun mondok itu
     * ribuan model per request, dan bebannya tumbuh terus tanpa pernah ada yang
     * memicu peringatan. chunkById menjaga memori tetap datar; aritmetikanya
     * dipertahankan identik dengan kedua method di atas (dikunci tes ekuivalensi),
     * jadi angka yang dibaca wali tidak bergeser satu digit pun.
     *
     * @param  Builder<KesantrianMutabaah>  $query
     * @return array{total_hari: int, rata_rata: int, breakdown: array<int, array<string, mixed>>}
     */
    public static function agregat(Builder $query, int $pesantrenId): array
    {
        $master = self::masterAktif($pesantrenId);

        $totalHari = 0;
        $skorPerPesantren = [];   // pesantren_id => total skor
        $hariPerPesantren = [];   // pesantren_id => jumlah baris
        $totalPerAmal = [];       // kode => akumulasi

        foreach ($master as $item) {
            $totalPerAmal[$item->kode] = 0;
        }

        $query->clone()->chunkById(500, function (Collection $batch) use (
            $master, &$totalHari, &$skorPerPesantren, &$hariPerPesantren, &$totalPerAmal
        ): void {
            $totalHari += $batch->count();

            foreach ($batch as $rec) {
                $pid = (int) $rec->pesantren_id;
                $skorPerPesantren[$pid] = ($skorPerPesantren[$pid] ?? 0) + self::skor($rec);
                $hariPerPesantren[$pid] = ($hariPerPesantren[$pid] ?? 0) + 1;

                $amalan = $rec->amalan ?? [];

                foreach ($master as $item) {
                    $totalPerAmal[$item->kode] += $item->tipe === 'hitungan'
                        ? (float) ($amalan[$item->kode] ?? 0)
                        : ((bool) ($amalan[$item->kode] ?? false) ? 1 : 0);
                }
            }
        });

        // Persis penjumlahan yang dilakukan persentaseRataRata(): penyebutnya
        // maxScore per pesantren dikali jumlah baris pesantren itu.
        $totalSkor = 0;
        $totalMaks = 0;

        foreach ($hariPerPesantren as $pid => $jumlah) {
            $totalSkor += $skorPerPesantren[$pid];
            $totalMaks += self::maxScore($pid) * $jumlah;
        }

        $breakdown = $master->map(function (KesantrianAmalMaster $item) use ($totalPerAmal, $totalHari) {
            $total = $totalPerAmal[$item->kode];
            $max = $item->tipe === 'hitungan' ? $totalHari * $item->nilai_maks : $totalHari;

            return [
                'kode' => $item->kode,
                'tipe' => $item->tipe,
                'icon' => $item->icon ?: '✅',
                'label' => $item->label,
                'total' => $total,
                'max' => $max,
                'unit' => $item->satuan,
                'pct' => $max > 0 ? (int) round($total / $max * 100) : 0,
            ];
        })->values()->all();

        return [
            'total_hari' => $totalHari,
            'rata_rata' => $totalMaks > 0 ? (int) round($totalSkor / $totalMaks * 100) : 0,
            'breakdown' => $breakdown,
        ];
    }

    /** Reset cache master amal — dipakai di test atau setelah amal_master diubah dalam request yang sama. */
    public static function clearCache(): void
    {
        self::$masterCache = [];
    }
}
