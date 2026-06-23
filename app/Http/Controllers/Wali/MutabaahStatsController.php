<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianMutabaah;
use App\Services\MutabaahScoreCalculator;

class MutabaahStatsController extends Controller
{
    public function show(int $santriId)
    {
        $wali   = auth()->user();
        $santri = $wali->anakSantri()->with(['kelas', 'kamar'])->findOrFail($santriId);

        $semua = KesantrianMutabaah::where('santri_id', $santri->id)
            ->orderBy('tanggal')
            ->get();

        $totalHari      = $semua->count();
        $rataRata       = MutabaahScoreCalculator::persentaseRataRata($semua);
        $breakdownAmal  = MutabaahScoreCalculator::breakdown($semua, $santri->pesantren_id);
        $amalMasterList = MutabaahScoreCalculator::masterAktif($santri->pesantren_id);

        // Trend rata-rata skor per bulan (12 bulan terakhir)
        $awalTren   = now()->subMonths(11)->startOfMonth();
        $trendGroup = $semua->filter(fn (KesantrianMutabaah $m) => $m->tanggal->greaterThanOrEqualTo($awalTren))
            ->groupBy(fn (KesantrianMutabaah $m) => $m->tanggal->format('Y-m'));

        $bulanLabels   = [];
        $dataAvgPct    = [];
        $dataTotalHari = [];
        for ($i = 11; $i >= 0; $i--) {
            $key   = now()->subMonths($i)->format('Y-m');
            $group = $trendGroup->get($key, collect());

            $bulanLabels[]   = now()->subMonths($i)->translatedFormat('M Y');
            $dataAvgPct[]    = MutabaahScoreCalculator::persentaseRataRata($group);
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
