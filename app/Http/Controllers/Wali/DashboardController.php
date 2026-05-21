<?php

// File: app/Http/Controllers/Wali/DashboardController.php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\TahfidzProgress;
use App\Models\TahfidzRapor;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $wali = auth()->user();

        // Ambil semua anak santri milik wali ini
        $anakList = $wali->anakSantri()
            ->with(['pesantren', 'pembimbing'])
            ->where('status_aktif', true)
            ->get();

        // Jika hanya 1 anak, langsung redirect ke detail
        if ($anakList->count() === 1) {
            return redirect()->route('wali.santri.show', $anakList->first()->id);
        }

        // ── Santri yang sedang aktif dipantau (default: anak pertama) ─────────
        $activeSantriId = request('santri_id', $anakList->first()?->id);
        $activeSantri   = $anakList->firstWhere('id', $activeSantriId) ?? $anakList->first();

        // ── 1. Estimasi juz hafalan ───────────────────────────────────────────
        $totalSetoran = TahfidzProgress::where('santri_id', $activeSantri?->id)
            ->where('tipe_setoran', 'Sabaq')
            ->count();

        // 1 juz ≈ 20 setoran sabaq (pendekatan proxy sederhana)
        $estimasiJuz = $activeSantri ? min(30, round($totalSetoran / 20, 1)) : 0;

        // ── 2. Persentase amalan minggu ini ───────────────────────────────────
        $startOfWeek = now()->startOfWeek();
        $endOfWeek   = now()->endOfWeek();

        $mutabaahMingguIni = KesantrianMutabaah::where('santri_id', $activeSantri?->id)
            ->whereBetween('tanggal', [$startOfWeek, $endOfWeek])
            ->get();

        $totalHariMingguIni = now()->dayOfWeek ?: 7; // hari yang sudah lewat
        $totalPoinMaksimal  = $totalHariMingguIni * 9; // 9 amalan per hari

        $totalPoinDidapat = 0;
        foreach ($mutabaahMingguIni as $m) {
            $totalPoinDidapat += $m->jamaah_5_waktu >= 5 ? 1 : 0;
            $totalPoinDidapat += $m->is_rawatib      ? 1 : 0;
            $totalPoinDidapat += $m->is_shalat_malam ? 1 : 0;
            $totalPoinDidapat += $m->is_dhuha        ? 1 : 0;
            $totalPoinDidapat += $m->is_tilawah_1juz ? 1 : 0;
            $totalPoinDidapat += $m->is_infak        ? 1 : 0;
            $totalPoinDidapat += $m->is_puasa        ? 1 : 0;
        }

        $persentaseAmalan = $totalPoinMaksimal > 0
            ? round(($totalPoinDidapat / $totalPoinMaksimal) * 100)
            : 0;

        // ── 3. Status kesehatan terkini ───────────────────────────────────────
        $kesehatanTerkini = KesantrianKesehatan::where('santri_id', $activeSantri?->id)
            ->latest('tanggal_periksa')
            ->first();

        // ── 4. Nilai rapor tahfidz terakhir ───────────────────────────────────
        $raporTerakhir = TahfidzRapor::where('santri_id', $activeSantri?->id)
            ->latest('created_at')
            ->first();

        return view('wali.dashboard', compact(
            'wali',
            'anakList',
            'activeSantri',
            'activeSantriId',
            'estimasiJuz',
            'persentaseAmalan',
            'kesehatanTerkini',
            'raporTerakhir',
        ));
    }
}
