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
use App\Services\MutabaahScoreCalculator;
use App\Services\TahfidzJuzCalculator;

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

        // ── Summary Card 1: Capaian Juz Hafalan ──────────────────────────────
        $juz = TahfidzJuzCalculator::calculate($santri->id);

        // ── Summary Card 2: Persentase Amalan 7 Hari Terakhir ────────────────
        $mutabaahMingguIni = KesantrianMutabaah::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->get();

        $persentaseAmalanMingguIni = MutabaahScoreCalculator::persentaseRataRata($mutabaahMingguIni);

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
            'juz',
            'persentaseAmalanMingguIni',
            'mutabaahWeek',
            'statusKesehatanTerkini',
            'raporTahfidzTerakhir',
            'prestasi',
        );
    }
}
