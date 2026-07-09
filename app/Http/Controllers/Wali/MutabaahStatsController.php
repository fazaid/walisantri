<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wali\Concerns\ResolvesSantriMilikWali;
use App\Models\KesantrianMutabaah;
use App\Services\MutabaahScoreCalculator;
use App\Services\TrendBulanan;

class MutabaahStatsController extends Controller
{
    use ResolvesSantriMilikWali;

    public function show(int $santriId)
    {
        $santri = $this->santriMilikWali($santriId);

        $semua = KesantrianMutabaah::where('santri_id', $santri->id)
            ->orderBy('tanggal')
            ->get();

        $totalHari = $semua->count();
        $rataRata = MutabaahScoreCalculator::persentaseRataRata($semua);
        $breakdownAmal = MutabaahScoreCalculator::breakdown($semua, $santri->pesantren_id);
        $amalMasterList = MutabaahScoreCalculator::masterAktif($santri->pesantren_id);

        // Trend rata-rata skor per bulan (12 bulan terakhir)
        $awalTren = now()->subMonths(11)->startOfMonth();
        $trendGroup = $semua->filter(fn (KesantrianMutabaah $m) => $m->tanggal->greaterThanOrEqualTo($awalTren))
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

        // Riwayat 30 hari terakhir
        $riwayat = $semua->sortByDesc('tanggal')->take(30)->values();

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
