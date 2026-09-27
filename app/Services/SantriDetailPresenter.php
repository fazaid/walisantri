<?php

namespace App\Services;

use App\Enums\Modul;
use App\Models\KesantrianInventaris;
use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\MasterPengumuman;
use App\Models\PrestasiSantri;
use App\Models\Santri;
use App\Models\SantriEkskul;
use App\Models\TahfidzProgress;
use App\Services\Rapor\RaporAkademikData;
use App\Support\Waktu;
use Illuminate\Support\Collection;

class SantriDetailPresenter
{
    /**
     * Data lengkap untuk halaman detail satu santri (dipakai ReportController & dashboard wali ber-1-anak).
     *
     * Modul yang dimatikan pesantren tidak sekadar disembunyikan di Blade — query-nya
     * memang tidak dijalankan. Menjaganya hanya di view berarti pesantren yang
     * mematikan Kesantrian tetap membayar empat query di setiap tampilan halaman ini.
     *
     * ⚠️ Modulnya dibaca dari $santri->pesantren_id, BUKAN dari konteks tenant.
     * Rute magic link (wali.magic.report) tidak memakai middleware tenant.resolve,
     * jadi global scope 'pesantren' bisa tidak terisi di sana — persis alasan yang
     * sama yang membuat query pengumuman di bawah memakai withoutGlobalScope.
     * Salahnya tidak akan memunculkan galat apa pun; wali cuma melihat seksi yang
     * bukan miliknya.
     */
    public static function detail(Santri $santri): array
    {
        $pesantrenId = $santri->pesantren_id;
        $tahfidzAktif = Modul::Tahfidz->aktif($pesantrenId);
        $kesantrianAktif = Modul::Kesantrian->aktif($pesantrenId);
        $akademikAktif = Modul::Akademik->aktif($pesantrenId);
        $presensiAktif = Modul::Presensi->aktif($pesantrenId);

        // 5 saja — dashboard cuma perlu sekilas-pandang; 10 terakhir ada di halaman Statistik Tahfidz.
        $tahfidzRecent = $tahfidzAktif
            ? TahfidzProgress::where('santri_id', $santri->id)
                ->orderByDesc('tanggal')
                ->limit(5)
                ->get()
            : collect();

        $kesehatanRecent = $kesantrianAktif
            ? KesantrianKesehatan::where('santri_id', $santri->id)
                ->orderByDesc('tanggal_periksa')
                ->limit(5)
                ->get()
            : collect();

        // Bentuknya dipertahankan (kunci juz_hafal tetap ada) supaya pembaca di
        // Blade tidak perlu tahu modulnya mati.
        $juz = $tahfidzAktif
            ? TahfidzJuzCalculator::calculate($santri->id)
            : ['juz_hafal' => 0.0];

        $mutabaahMingguIni = $kesantrianAktif
            ? KesantrianMutabaah::where('santri_id', $santri->id)
                ->whereBetween('tanggal', [Waktu::sekarang()->subDays(6)->toDateString(), Waktu::sekarang()->toDateString()])
                ->get()
            : collect();

        $persentaseAmalanMingguIni = MutabaahScoreCalculator::persentaseRataRata($mutabaahMingguIni);
        $mutabaahWeek = $mutabaahMingguIni->keyBy(fn ($m) => $m->tanggal->toDateString());

        $latestKesehatan = $kesantrianAktif
            ? KesantrianKesehatan::where('santri_id', $santri->id)
                ->orderByDesc('tanggal_periksa')
                ->first()
            : null;

        $statusKesehatanTerkini = $latestKesehatan ? [
            'tanggal_periksa' => $latestKesehatan->tanggal_periksa,
            'kategori_keluhan' => $latestKesehatan->kategori_keluhan,
            'status_pemulihan' => $latestKesehatan->status_pemulihan,
        ] : null;

        // Semester berjalan, sama seperti nilai default RaporPage — dashboard cuma
        // butuh sekilas-pandang; rincian per mapel & periode lain ada di halaman Rapor.
        $nilaiAkademik = null;
        if ($akademikAktif) {
            $raporAkademik = RaporAkademikData::untuk(
                $santri->id,
                TahunAjaranOptions::current(),
                TahunAjaranOptions::currentPeriode(),
            );

            $nilaiAkademik = [
                'ada_data' => $raporAkademik['ada_data'],
                'rata_rata' => $raporAkademik['rata_rata'],
                'jumlah_mapel' => $raporAkademik['nilai']->count(),
            ];
        }

        $kehadiranBulanIni = null;
        if ($presensiAktif) {
            $rekapKehadiran = PresensiRekap::untuk(
                $pesantrenId,
                Waktu::sekarang()->startOfMonth()->toDateString(),
                Waktu::sekarang()->toDateString(),
                santriId: $santri->id,
            )->satuSantri();

            // total_tercatat > 0: bedakan "belum pernah diabsen" dari "0% hadir" —
            // pola yang sama dipakai App\Services\Rapor\RaporPresensiData.
            $kehadiranBulanIni = $rekapKehadiran ? [
                'ada_data' => $rekapKehadiran->total_tercatat > 0,
                'persen_kehadiran' => $rekapKehadiran->persen_kehadiran,
                'hadir_efektif' => $rekapKehadiran->hadir_efektif,
                'hari_efektif' => $rekapKehadiran->hari_efektif,
            ] : null;
        }

        // Prestasi milik Cluster Santri — inti, tidak pernah bisa dimatikan.
        $prestasi = PrestasiSantri::withoutGlobalScope('pesantren')
            ->where('santri_id', $santri->id)
            ->orderByDesc('tanggal')
            ->get();

        $ekskul = $akademikAktif
            ? SantriEkskul::where('santri_id', $santri->id)
                ->with('ekskulMaster')
                ->orderBy('aktif', 'desc')
                ->orderBy('tanggal_mulai', 'asc')
                ->get()
            : collect();

        $totalInventaris = $kesantrianAktif
            ? KesantrianInventaris::where('santri_id', $santri->id)->count()
            : 0;

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
            'nilaiAkademik',
            'kehadiranBulanIni',
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
            ->whereBetween('tanggal', [Waktu::sekarang()->subDays(6)->toDateString(), Waktu::sekarang()->toDateString()])
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
