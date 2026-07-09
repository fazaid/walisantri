<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wali\Concerns\ResolvesSantriMilikWali;
use App\Models\TahfidzProgress;
use App\Services\TahfidzJuzCalculator;
use App\Services\TrendBulanan;
use Illuminate\Support\Facades\DB;

class TahfidzStatsController extends Controller
{
    use ResolvesSantriMilikWali;

    public function show(int $santriId)
    {
        $santri = $this->santriMilikWali($santriId);

        // Setoran per bulan (12 bulan terakhir) — semua tipe
        $setoranPerBulan = TahfidzProgress::where('santri_id', $santri->id)
            ->where('tanggal', '>=', now()->subMonths(11)->startOfMonth())
            ->select(
                DB::raw("TO_CHAR(tanggal, 'YYYY-MM') as bulan"),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN tipe_setoran = 'Sabaq' THEN 1 ELSE 0 END) as sabaq"),
                DB::raw("SUM(CASE WHEN tipe_setoran = 'Sabqi' THEN 1 ELSE 0 END) as sabqi"),
                DB::raw("SUM(CASE WHEN tipe_setoran = 'Manzil' THEN 1 ELSE 0 END) as manzil"),
            )
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        // Isi bulan yang kosong agar grafik 12 bulan penuh
        $bulanLabels = [];
        $dataSabaq = [];
        $dataSabqi = [];
        $dataManzil = [];

        foreach (TrendBulanan::duaBelasBulanTerakhir() as $bulan) {
            $row = $setoranPerBulan->firstWhere('bulan', $bulan['key']);

            $bulanLabels[] = $bulan['label'];
            $dataSabaq[] = $row?->sabaq ?? 0;
            $dataSabqi[] = $row?->sabqi ?? 0;
            $dataManzil[] = $row?->manzil ?? 0;
        }

        // Total keseluruhan
        $totalSabaq = TahfidzProgress::where('santri_id', $santri->id)->where('tipe_setoran', 'Sabaq')->count();
        $totalSabqi = TahfidzProgress::where('santri_id', $santri->id)->where('tipe_setoran', 'Sabqi')->count();
        $totalManzil = TahfidzProgress::where('santri_id', $santri->id)->where('tipe_setoran', 'Manzil')->count();

        $juz = TahfidzJuzCalculator::calculate($santri->id);

        // Distribusi nilai kelancaran
        $distribusiNilai = TahfidzProgress::where('santri_id', $santri->id)
            ->select('nilai_kelancaran', DB::raw('COUNT(*) as total'))
            ->groupBy('nilai_kelancaran')
            ->pluck('total', 'nilai_kelancaran');

        // 10 setoran terbaru
        $setoranTerbaru = TahfidzProgress::where('santri_id', $santri->id)
            ->orderByDesc('tanggal')
            ->limit(10)
            ->get();

        return view('wali.tahfidz.stats', compact(
            'santri',
            'bulanLabels',
            'dataSabaq',
            'dataSabqi',
            'dataManzil',
            'totalSabaq',
            'totalSabqi',
            'totalManzil',
            'juz',
            'distribusiNilai',
            'setoranTerbaru',
        ));
    }
}
