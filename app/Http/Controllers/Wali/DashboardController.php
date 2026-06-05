<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\MasterPengumuman;
use App\Models\TahfidzProgress;
use App\Models\TahfidzRapor;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $wali = auth()->user();

        $anakList = $wali->anakSantri()
            ->with(['pesantren', 'kelas', 'kamar', 'pembimbing'])
            ->where('status_aktif', true)
            ->get();

        $children = $anakList->map(fn ($santri) => $this->buildChildData($santri));

        // Alert kesehatan lintas anak
        $alertKesehatan = $children
            ->filter(fn ($c) => in_array($c['statusKesehatan']['status_pemulihan'] ?? null, ['Istirahat_Total', 'Rujukan_Luar']))
            ->map(fn ($c) => [
                'nama'             => $c['santri']->nama_lengkap,
                'status'           => $c['statusKesehatan']['status_pemulihan'],
                'tanggal_periksa'  => $c['statusKesehatan']['tanggal_periksa'],
            ]);

        $pengumuman = MasterPengumuman::where('pesantren_id', $wali->pesantren_id)
            ->forWali()->latest()->limit(5)->get();

        $pengumumanCentral = MasterPengumuman::withoutGlobalScope('pesantren')
            ->whereNull('pesantren_id')
            ->forWali()->latest()->limit(3)->get();

        return view('wali.dashboard', compact(
            'wali',
            'children',
            'alertKesehatan',
            'pengumuman',
            'pengumumanCentral',
        ));
    }

    private function buildChildData($santri): array
    {
        // Estimasi juz
        $sumMaxAyat = TahfidzProgress::where('santri_id', $santri->id)
            ->select('nama_surah', DB::raw('MAX(ayat_selesai) as max_ayat'))
            ->groupBy('nama_surah')->pluck('max_ayat')->sum();
        $totalJuz = $sumMaxAyat > 0 ? round($sumMaxAyat / 604 * 30, 1) : 0;

        // Persentase amalan 7 hari terakhir
        $mutabaah = KesantrianMutabaah::where('santri_id', $santri->id)
            ->whereBetween('tanggal', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->get();
        $persentaseAmalan = 0;
        if ($mutabaah->isNotEmpty()) {
            $skor = $mutabaah->sum(fn ($m) =>
                ($m->jamaah_5_waktu * 5)
                + ($m->is_rawatib      ? 7 : 0)
                + ($m->is_shalat_malam ? 7 : 0)
                + ($m->is_dhuha        ? 7 : 0)
                + ($m->is_tilawah_1juz ? 7 : 0)
                + ($m->is_infak        ? 7 : 0)
                + ($m->is_puasa        ? 7 : 0)
            );
            $persentaseAmalan = (int) round(($skor / (67 * 7)) * 100);
        }

        // Status kesehatan terkini
        $latestKesehatan  = KesantrianKesehatan::where('santri_id', $santri->id)
            ->orderByDesc('tanggal_periksa')->first();
        $statusKesehatan  = $latestKesehatan ? [
            'tanggal_periksa'  => $latestKesehatan->tanggal_periksa,
            'status_pemulihan' => $latestKesehatan->status_pemulihan,
        ] : null;

        // Rapor terakhir
        $latestRapor     = TahfidzRapor::where('santri_id', $santri->id)
            ->orderByDesc('created_at')->first();
        $raporTerakhir   = $latestRapor ? [
            'periode'       => $latestRapor->periode,
            'tahun_ajaran'  => $latestRapor->tahun_ajaran,
            'nilai_hafalan' => $latestRapor->nilai_hafalan,
        ] : null;

        // Riwayat setoran & kesehatan
        $tahfidzRecent   = TahfidzProgress::where('santri_id', $santri->id)
            ->orderByDesc('tanggal')->limit(10)->get();
        $kesehatanRecent = KesantrianKesehatan::where('santri_id', $santri->id)
            ->orderByDesc('tanggal_periksa')->limit(5)->get();

        return compact(
            'santri',
            'totalJuz',
            'persentaseAmalan',
            'statusKesehatan',
            'raporTerakhir',
            'tahfidzRecent',
            'kesehatanRecent',
        );
    }
}
