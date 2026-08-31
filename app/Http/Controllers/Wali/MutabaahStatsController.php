<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wali\Concerns\ResolvesSantriMilikWali;
use App\Models\KesantrianMutabaah;
use App\Services\MutabaahScoreCalculator;
use App\Services\TrendBulanan;
use App\Support\Waktu;

class MutabaahStatsController extends Controller
{
    use ResolvesSantriMilikWali;

    public function show(int $santriId)
    {
        $santri = $this->santriMilikWali($santriId);

        // Halaman ini menampilkan tiga rentang yang berbeda, dan dulu ketiganya
        // dilayani satu ->get() tanpa batas tanggal: seluruh riwayat santri
        // dihidupkan jadi model Eloquent tiap kali halaman dibuka, lalu 99%-nya
        // dibuang oleh filter di PHP. Untuk santri yang sudah bertahun-tahun
        // mondok itu ribuan model per request — beban yang tumbuh diam-diam.
        // Sekarang tiap rentang punya querynya sendiri, dan agregat seumur-hidup
        // dihitung streaming (chunk) supaya semantik "seluruh waktu tercatat"
        // yang tertulis di view tetap utuh tanpa menahan barisnya di memori.
        $agregat = MutabaahScoreCalculator::agregat(
            KesantrianMutabaah::where('santri_id', $santri->id),
            $santri->pesantren_id,
        );

        $totalHari = $agregat['total_hari'];
        $rataRata = $agregat['rata_rata'];
        $breakdownAmal = $agregat['breakdown'];
        $amalMasterList = MutabaahScoreCalculator::masterAktif($santri->pesantren_id);

        // Trend rata-rata skor per bulan (12 bulan terakhir)
        // startOfMonth() SEBELUM subMonths() — lihat catatan di TrendBulanan.
        $awalTren = Waktu::sekarang()->startOfMonth()->subMonths(11);
        $trendGroup = KesantrianMutabaah::where('santri_id', $santri->id)
            ->whereDate('tanggal', '>=', $awalTren->toDateString())
            ->orderBy('tanggal')
            ->get()
            ->groupBy(fn (KesantrianMutabaah $m) => $m->tanggal->format('Y-m'));

        $bulanLabels = [];
        $dataAvgPct = [];
        $dataTotalHari = [];
        foreach (TrendBulanan::duaBelasBulanTerakhir() as $bulan) {
            $group = $trendGroup->get($bulan['key'], collect());

            $bulanLabels[] = $bulan['label'];
            $dataAvgPct[] = MutabaahScoreCalculator::persentaseRataRata($group);
            $dataTotalHari[] = $group->count();
        }

        // Riwayat 30 catatan terakhir — sengaja TIDAK dibatasi 12 bulan seperti
        // tren di atas: santri yang lama tidak tercatat tetap harus bisa melihat
        // catatan terakhirnya, persis seperti perilaku sebelumnya.
        $riwayat = KesantrianMutabaah::where('santri_id', $santri->id)
            ->orderByDesc('tanggal')
            ->limit(30)
            ->get();

        return view('wali.mutabaah.stats', compact(
            'santri',
            'totalHari',
            'rataRata',
            'breakdownAmal',
            'amalMasterList',
            'bulanLabels',
            'dataAvgPct',
            'dataTotalHari',
            'riwayat',
        ));
    }
}
