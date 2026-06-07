<?php

// File: app/Http/Controllers/Wali/ReportController.php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\PrestasiSantri;
use App\Models\Santri;
use App\Models\TahfidzProgress;
use App\Models\TahfidzRapor;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // Detail santri — diakses wali yang sudah login normal
    public function show(int $santriId)
    {
        $wali = auth()->user();

        // Pastikan santri ini milik wali yang login
        $santri = $wali->anakSantri()
            ->with(['pembimbing', 'pesantren', 'kelas', 'kamar'])
            ->findOrFail($santriId);

        return view('wali.santri.show', $this->buildPayload($santri));
    }

    // Magic Link — diakses via /report/{uuid}
    public function showByUuid(string $uuid)
    {
        // VerifyMagicToken middleware sudah handle auth & validasi UUID
        $santriId = session('magic_link_santri_id');

        $santri = Santri::withoutGlobalScope('pesantren')
            ->with(['pembimbing', 'pesantren', 'kelas', 'kamar'])
            ->findOrFail($santriId);

        return view('wali.santri.show', $this->buildPayload($santri));
    }

    private function buildPayload(Santri $santri): array
    {
        // ── Existing queries ─────────────────────────────────────────────────
        $tahfidzRecent = TahfidzProgress::where('santri_id', $santri->id)
            ->orderByDesc('tanggal')
            ->limit(10)
            ->get();

        $kesehatanRecent = KesantrianKesehatan::where('santri_id', $santri->id)
            ->orderByDesc('tanggal_periksa')
            ->limit(5)
            ->get();

        // ── Summary Card 1: Total Juz Hafalan ────────────────────────────────
        // Per surah: ambil ayat_selesai tertinggi, lalu estimasi juz dari total ayat.
        $sumMaxAyat = TahfidzProgress::where('santri_id', $santri->id)
            ->select('nama_surah', DB::raw('MAX(ayat_selesai) as max_ayat'))
            ->groupBy('nama_surah')
            ->pluck('max_ayat')
            ->sum();

        $totalJuzHafalan = $sumMaxAyat > 0
            ? round($sumMaxAyat / 6236 * 30, 1)
            : 0;

        // ── Summary Card 2: Persentase Amalan 7 Hari Terakhir ────────────────
        $mutabaahMingguIni = KesantrianMutabaah::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->get();

        $persentaseAmalanMingguIni = 0;

        if ($mutabaahMingguIni->isNotEmpty()) {
            $totalSkor = $mutabaahMingguIni->sum(function ($m) {
                return ($m->jamaah_5_waktu * 5)   // max 25
                    + ($m->is_rawatib      ? 7 : 0)
                    + ($m->is_shalat_malam ? 7 : 0)
                    + ($m->is_dhuha        ? 7 : 0)
                    + ($m->is_tilawah_1juz ? 7 : 0)
                    + ($m->is_infak        ? 7 : 0)
                    + ($m->is_puasa        ? 7 : 0);
            });
            $persentaseAmalanMingguIni = (int) round(($totalSkor / (67 * 7)) * 100);
        }

        $mutabaahWeek = $mutabaahMingguIni->keyBy(fn ($m) => $m->tanggal->toDateString());

        // ── Summary Card 3: Status Kesehatan Terkini ─────────────────────────
        $latestKesehatan = KesantrianKesehatan::where('santri_id', $santri->id)
            ->orderByDesc('tanggal_periksa')
            ->first();

        $statusKesehatanTerkini = $latestKesehatan ? [
            'tanggal_periksa'  => $latestKesehatan->tanggal_periksa,
            'kategori_keluhan' => $latestKesehatan->kategori_keluhan,
            'status_pemulihan' => $latestKesehatan->status_pemulihan,
        ] : null;

        // ── Summary Card 4: Rapor Tahfidz Terakhir ───────────────────────────
        $latestRapor = TahfidzRapor::where('santri_id', $santri->id)
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

        return compact(
            'santri',
            'tahfidzRecent',
            'kesehatanRecent',
            'totalJuzHafalan',
            'persentaseAmalanMingguIni',
            'mutabaahWeek',
            'statusKesehatanTerkini',
            'raporTahfidzTerakhir',
            'prestasi',
        );
    }
}
