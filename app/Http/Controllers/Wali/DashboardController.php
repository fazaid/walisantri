<?php

// File: app/Http/Controllers/Wali/DashboardController.php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\MasterPengumuman;
use App\Models\TahfidzProgress;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $wali = auth()->user();

        $anakList = $wali->anakSantri()
            ->with(['pesantren', 'kelas', 'kamar'])
            ->where('status_aktif', true)
            ->get();

        $santriIds = $anakList->pluck('id');

        // Alert kesehatan: santri yang berstatus tidak sehat
        $alertKesehatan = KesantrianKesehatan::whereIn('santri_id', $santriIds)
            ->whereIn('status_pemulihan', ['Istirahat_Total', 'Rujukan_Luar'])
            ->whereIn('id', function ($q) use ($santriIds) {
                // Hanya ambil record terbaru per santri
                $q->select(DB::raw('MAX(id)'))
                  ->from('kesantrian_kesehatan')
                  ->whereIn('santri_id', $santriIds)
                  ->groupBy('santri_id');
            })
            ->with('santri')
            ->get()
            ->keyBy('santri_id');

        // Setoran tahfidz terakhir per santri
        $setoranTerakhir = TahfidzProgress::whereIn('santri_id', $santriIds)
            ->whereIn('id', function ($q) use ($santriIds) {
                $q->select(DB::raw('MAX(id)'))
                  ->from('tahfidz_progress')
                  ->whereIn('santri_id', $santriIds)
                  ->groupBy('santri_id');
            })
            ->get()
            ->keyBy('santri_id');

        // Persentase amalan 7 hari terakhir per santri
        $mutabaah = KesantrianMutabaah::whereIn('santri_id', $santriIds)
            ->whereBetween('tanggal', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->get()
            ->groupBy('santri_id');

        $persentaseAmalan = [];
        foreach ($santriIds as $id) {
            $records = $mutabaah[$id] ?? collect();
            if ($records->isEmpty()) {
                $persentaseAmalan[$id] = 0;
                continue;
            }
            $skor = $records->sum(fn ($m) =>
                ($m->jamaah_5_waktu * 5)
                + ($m->is_rawatib      ? 7 : 0)
                + ($m->is_shalat_malam ? 7 : 0)
                + ($m->is_dhuha        ? 7 : 0)
                + ($m->is_tilawah_1juz ? 7 : 0)
                + ($m->is_infak        ? 7 : 0)
                + ($m->is_puasa        ? 7 : 0)
            );
            $persentaseAmalan[$id] = (int) round(($skor / (67 * 7)) * 100);
        }

        // Pengumuman
        $pengumuman = MasterPengumuman::where('pesantren_id', $wali->pesantren_id)
            ->forWali()->latest()->limit(5)->get();

        $pengumumanCentral = MasterPengumuman::withoutGlobalScope('pesantren')
            ->whereNull('pesantren_id')
            ->forWali()->latest()->limit(3)->get();

        return view('wali.dashboard', compact(
            'wali',
            'anakList',
            'alertKesehatan',
            'setoranTerakhir',
            'persentaseAmalan',
            'pengumuman',
            'pengumumanCentral',
        ));
    }
}
