<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianKesehatan;
use Illuminate\Support\Facades\DB;

class KesehatanStatsController extends Controller
{
    public function show(int $santriId)
    {
        $wali   = auth()->user();
        $santri = $wali->anakSantri()->with(['kelas', 'kamar'])->findOrFail($santriId);

        // Pemeriksaan per bulan (12 bulan terakhir)
        $periksaPerBulan = KesantrianKesehatan::where('santri_id', $santri->id)
            ->where('tanggal_periksa', '>=', now()->subMonths(11)->startOfMonth())
            ->select(DB::raw("TO_CHAR(tanggal_periksa, 'YYYY-MM') as bulan"), DB::raw('COUNT(*) as total'))
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->pluck('total', 'bulan');

        $bulanLabels   = [];
        $dataPemeriksaan = [];
        for ($i = 11; $i >= 0; $i--) {
            $key             = now()->subMonths($i)->format('Y-m');
            $bulanLabels[]   = now()->subMonths($i)->translatedFormat('M Y');
            $dataPemeriksaan[] = $periksaPerBulan[$key] ?? 0;
        }

        // Distribusi kategori keluhan
        $distribusiKeluhan = KesantrianKesehatan::where('santri_id', $santri->id)
            ->select('kategori_keluhan', DB::raw('COUNT(*) as total'))
            ->groupBy('kategori_keluhan')
            ->orderByDesc('total')
            ->pluck('total', 'kategori_keluhan');

        // Distribusi status pemulihan
        $distribusiStatus = KesantrianKesehatan::where('santri_id', $santri->id)
            ->select('status_pemulihan', DB::raw('COUNT(*) as total'))
            ->groupBy('status_pemulihan')
            ->pluck('total', 'status_pemulihan');

        // Tren berat & tinggi badan (10 data terakhir yang punya nilai)
        $trenFisik = KesantrianKesehatan::where('santri_id', $santri->id)
            ->whereNotNull('berat_badan')
            ->whereNotNull('tinggi_badan')
            ->orderByDesc('tanggal_periksa')
            ->limit(10)
            ->get(['tanggal_periksa', 'berat_badan', 'tinggi_badan'])
            ->sortBy('tanggal_periksa')
            ->values();

        // Total & status terkini
        $totalPemeriksaan = KesantrianKesehatan::where('santri_id', $santri->id)->count();
        $statusTerkini    = KesantrianKesehatan::where('santri_id', $santri->id)
            ->orderByDesc('tanggal_periksa')->first();

        // Riwayat 10 terbaru
        $riwayat = KesantrianKesehatan::where('santri_id', $santri->id)
            ->orderByDesc('tanggal_periksa')
            ->limit(10)
            ->get();

        return view('wali.kesehatan.stats', compact(
            'santri',
            'bulanLabels',
            'dataPemeriksaan',
            'distribusiKeluhan',
            'distribusiStatus',
            'trenFisik',
            'totalPemeriksaan',
            'statusTerkini',
            'riwayat',
        ));
    }
}
