<?php

namespace App\Services;

use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\PrestasiSantri;
use App\Models\Santri;
use App\Models\SantriEkskul;
use App\Models\TahfidzProgress;
use App\Models\TahfidzUjian;

class SantriDetailPresenter
{
    /** Data lengkap untuk halaman detail satu santri (dipakai ReportController & dashboard wali ber-1-anak). */
    public static function detail(Santri $santri): array
    {
        $tahfidzRecent = TahfidzProgress::where('santri_id', $santri->id)
            ->orderByDesc('tanggal')
            ->limit(10)
            ->get();

        $kesehatanRecent = KesantrianKesehatan::where('santri_id', $santri->id)
            ->orderByDesc('tanggal_periksa')
            ->limit(5)
            ->get();

        $juz = TahfidzJuzCalculator::calculate($santri->id);

        $mutabaahMingguIni = KesantrianMutabaah::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->get();

        $persentaseAmalanMingguIni = MutabaahScoreCalculator::persentaseRataRata($mutabaahMingguIni);
        $mutabaahWeek = $mutabaahMingguIni->keyBy(fn ($m) => $m->tanggal->toDateString());

        $latestKesehatan = KesantrianKesehatan::where('santri_id', $santri->id)
            ->orderByDesc('tanggal_periksa')
            ->first();

        $statusKesehatanTerkini = $latestKesehatan ? [
            'tanggal_periksa'  => $latestKesehatan->tanggal_periksa,
            'kategori_keluhan' => $latestKesehatan->kategori_keluhan,
            'status_pemulihan' => $latestKesehatan->status_pemulihan,
        ] : null;

        $latestRapor = TahfidzUjian::where('santri_id', $santri->id)
            ->orderByDesc('created_at')
            ->first();

        $raporTahfidzTerakhir = $latestRapor ? [
            'periode'       => $latestRapor->periode,
            'tahun_ajaran'  => $latestRapor->tahun_ajaran,
            'nilai_hafalan' => $latestRapor->nilai_hafalan,
            'nilai_tilawah' => $latestRapor->nilai_tilawah,
            'nilai_tajwid'  => $latestRapor->nilai_tajwid,
            'nilai_makhraj' => $latestRapor->nilai_makhraj,
        ] : null;

        $prestasi = PrestasiSantri::withoutGlobalScope('pesantren')
            ->where('santri_id', $santri->id)
            ->orderByDesc('tanggal')
            ->get();

        $ekskul = SantriEkskul::where('santri_id', $santri->id)
            ->with('ekskulMaster')
            ->orderBy('aktif', 'desc')
            ->orderBy('tanggal_mulai', 'asc')
            ->get();

        return compact(
            'tahfidzRecent',
            'kesehatanRecent',
            'juz',
            'persentaseAmalanMingguIni',
            'mutabaahWeek',
            'statusKesehatanTerkini',
            'raporTahfidzTerakhir',
            'prestasi',
            'ekskul',
        );
    }

    /** Data ringkas untuk kartu santri di dashboard wali ber->1-anak. */
    public static function cardSummary(Santri $santri): array
    {
        $juz = TahfidzJuzCalculator::calculate($santri->id);

        $mutabaah = KesantrianMutabaah::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->get();
        $persentaseAmalan = MutabaahScoreCalculator::persentaseRataRata($mutabaah);

        $latestKesehatan = KesantrianKesehatan::where('santri_id', $santri->id)
            ->orderByDesc('tanggal_periksa')
            ->first();

        $statusKesehatan = $latestKesehatan ? [
            'tanggal_periksa'  => $latestKesehatan->tanggal_periksa,
            'status_pemulihan' => $latestKesehatan->status_pemulihan,
        ] : null;

        return compact('juz', 'persentaseAmalan', 'statusKesehatan');
    }
}
