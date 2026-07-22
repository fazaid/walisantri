<?php

namespace App\Services;

use App\Models\KesantrianInventaris;
use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\MasterPengumuman;
use App\Models\PrestasiSantri;
use App\Models\Santri;
use App\Models\SantriEkskul;
use App\Models\TahfidzProgress;
use App\Models\TahfidzUjian;
use Illuminate\Support\Collection;

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
            'tanggal_periksa' => $latestKesehatan->tanggal_periksa,
            'kategori_keluhan' => $latestKesehatan->kategori_keluhan,
            'status_pemulihan' => $latestKesehatan->status_pemulihan,
        ] : null;

        $latestRapor = TahfidzUjian::where('santri_id', $santri->id)
            ->orderByDesc('created_at')
            ->first();

        $raporTahfidzTerakhir = $latestRapor ? [
            'periode' => $latestRapor->periode,
            'tahun_ajaran' => $latestRapor->tahun_ajaran,
            'nilai_hafalan' => $latestRapor->nilai_hafalan,
            'nilai_tilawah' => $latestRapor->nilai_tilawah,
            'nilai_tajwid' => $latestRapor->nilai_tajwid,
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

        $totalInventaris = KesantrianInventaris::where('santri_id', $santri->id)->count();

        // Pengumuman untuk halaman report — disurutkan ke sesi magic link & preview
        // yang tak punya akses dashboard/nav. Di-scope eksplisit ke pesantren santri
        // (bukan konteks tenant) agar deterministik: route magic link tak memakai
        // tenant.resolve, jadi global scope 'pesantren' bisa tak terisi.
        $pengumumanPesantren = MasterPengumuman::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $santri->pesantren_id)
            ->forWali()->latest()->limit(5)->get();

        $pengumumanGlobal = MasterPengumuman::withoutGlobalScope('pesantren')
            ->whereNull('pesantren_id')
            ->forWali()->latest()->limit(3)->get();

        $pengumumanReport = $pengumumanPesantren->merge($pengumumanGlobal)
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

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
            'totalInventaris',
            'pengumumanReport',
        );
    }

    /**
     * Data ringkas untuk kartu-kartu santri di dashboard wali ber->banyak-anak,
     * dibatch jadi 3 query total (bukan 3 query × jumlah anak).
     *
     * @return Collection<int, array{juz: array, persentaseAmalan: int, statusKesehatan: ?array}> keyed by santri id
     */
    public static function cardSummaryMany(Collection $santriList): Collection
    {
        $ids = $santriList->pluck('id')->all();

        $juzBySantri = TahfidzJuzCalculator::calculateMany($ids);

        $mutabaahBySantri = KesantrianMutabaah::whereIn('santri_id', $ids)
            ->whereBetween('tanggal', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->get()
            ->groupBy('santri_id');

        $latestKesehatanBySantri = KesantrianKesehatan::whereIn('santri_id', $ids)
            ->orderByDesc('tanggal_periksa')
            ->get()
            ->groupBy('santri_id')
            ->map(fn (Collection $group) => $group->first());

        return $santriList->mapWithKeys(function (Santri $santri) use ($juzBySantri, $mutabaahBySantri, $latestKesehatanBySantri) {
            $mutabaah = $mutabaahBySantri->get($santri->id, collect());
            $latestKesehatan = $latestKesehatanBySantri->get($santri->id);

            return [$santri->id => [
                'juz' => $juzBySantri[$santri->id],
                'persentaseAmalan' => MutabaahScoreCalculator::persentaseRataRata($mutabaah),
                'statusKesehatan' => $latestKesehatan ? [
                    'tanggal_periksa' => $latestKesehatan->tanggal_periksa,
                    'status_pemulihan' => $latestKesehatan->status_pemulihan,
                ] : null,
            ]];
        });
    }
}
