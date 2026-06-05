<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianMutabaah;
use Illuminate\Support\Facades\DB;

class MutabaahStatsController extends Controller
{
    public function show(int $santriId)
    {
        $wali   = auth()->user();
        $santri = $wali->anakSantri()->with(['kelas', 'kamar'])->findOrFail($santriId);

        // Agregat keseluruhan (1 query)
        $agregat = KesantrianMutabaah::where('santri_id', $santri->id)
            ->select(
                DB::raw('COUNT(*) as total_hari'),
                DB::raw('SUM(jamaah_5_waktu) as total_jamaah'),
                DB::raw('SUM(CASE WHEN is_rawatib THEN 1 ELSE 0 END) as total_rawatib'),
                DB::raw('SUM(CASE WHEN is_shalat_malam THEN 1 ELSE 0 END) as total_shalat_malam'),
                DB::raw('SUM(CASE WHEN is_dhuha THEN 1 ELSE 0 END) as total_dhuha'),
                DB::raw('SUM(CASE WHEN is_tilawah_1juz THEN 1 ELSE 0 END) as total_tilawah'),
                DB::raw('SUM(CASE WHEN is_infak THEN 1 ELSE 0 END) as total_infak'),
                DB::raw('SUM(CASE WHEN is_puasa THEN 1 ELSE 0 END) as total_puasa'),
                DB::raw("COALESCE(ROUND(AVG(
                    (jamaah_5_waktu * 5)
                    + (CASE WHEN is_rawatib THEN 7 ELSE 0 END)
                    + (CASE WHEN is_shalat_malam THEN 7 ELSE 0 END)
                    + (CASE WHEN is_dhuha THEN 7 ELSE 0 END)
                    + (CASE WHEN is_tilawah_1juz THEN 7 ELSE 0 END)
                    + (CASE WHEN is_infak THEN 7 ELSE 0 END)
                    + (CASE WHEN is_puasa THEN 7 ELSE 0 END)
                ) / 67.0 * 100), 0) as rata_rata_pct"),
            )
            ->first();

        $totalHari = (int) ($agregat->total_hari ?? 0);
        $rataRata  = (int) ($agregat->rata_rata_pct ?? 0);

        $breakdownAmal = [
            ['icon' => '🕌', 'label' => 'Berjamaah',    'total' => (int) $agregat->total_jamaah,       'max' => $totalHari * 5, 'unit' => 'waktu'],
            ['icon' => '🌙', 'label' => 'Rawatib',       'total' => (int) $agregat->total_rawatib,      'max' => $totalHari,     'unit' => 'hari'],
            ['icon' => '🌃', 'label' => 'Shalat Malam',  'total' => (int) $agregat->total_shalat_malam, 'max' => $totalHari,     'unit' => 'hari'],
            ['icon' => '🌅', 'label' => 'Dhuha',         'total' => (int) $agregat->total_dhuha,        'max' => $totalHari,     'unit' => 'hari'],
            ['icon' => '📖', 'label' => 'Tilawah 1 Juz', 'total' => (int) $agregat->total_tilawah,      'max' => $totalHari,     'unit' => 'hari'],
            ['icon' => '💰', 'label' => 'Infak',         'total' => (int) $agregat->total_infak,        'max' => $totalHari,     'unit' => 'hari'],
            ['icon' => '🤲', 'label' => 'Puasa Sunnah',  'total' => (int) $agregat->total_puasa,        'max' => $totalHari,     'unit' => 'hari'],
        ];

        // Trend rata-rata skor per bulan (12 bulan terakhir)
        $trendBulan = KesantrianMutabaah::where('santri_id', $santri->id)
            ->where('tanggal', '>=', now()->subMonths(11)->startOfMonth())
            ->select(
                DB::raw("TO_CHAR(tanggal, 'YYYY-MM') as bulan"),
                DB::raw("COALESCE(ROUND(AVG(
                    (jamaah_5_waktu * 5)
                    + (CASE WHEN is_rawatib THEN 7 ELSE 0 END)
                    + (CASE WHEN is_shalat_malam THEN 7 ELSE 0 END)
                    + (CASE WHEN is_dhuha THEN 7 ELSE 0 END)
                    + (CASE WHEN is_tilawah_1juz THEN 7 ELSE 0 END)
                    + (CASE WHEN is_infak THEN 7 ELSE 0 END)
                    + (CASE WHEN is_puasa THEN 7 ELSE 0 END)
                ) / 67.0 * 100), 0) as avg_pct"),
                DB::raw('COUNT(*) as total_hari'),
            )
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $bulanLabels = [];
        $dataAvgPct  = [];
        $dataTotalHari = [];
        for ($i = 11; $i >= 0; $i--) {
            $key             = now()->subMonths($i)->format('Y-m');
            $bulanLabels[]   = now()->subMonths($i)->translatedFormat('M Y');
            $dataAvgPct[]    = (int) ($trendBulan[$key]->avg_pct ?? 0);
            $dataTotalHari[] = (int) ($trendBulan[$key]->total_hari ?? 0);
        }

        // Riwayat 30 hari terakhir
        $riwayat = KesantrianMutabaah::where('santri_id', $santri->id)
            ->orderByDesc('tanggal')
            ->limit(30)
            ->get();

        return view('wali.mutabaah.stats', compact(
            'santri',
            'totalHari',
            'rataRata',
            'breakdownAmal',
            'bulanLabels',
            'dataAvgPct',
            'dataTotalHari',
            'riwayat',
        ));
    }
}
