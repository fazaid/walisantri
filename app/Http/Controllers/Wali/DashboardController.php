<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Enums\StatusTagihanSpp;
use App\Models\MasterPengumuman;
use App\Models\TagihanSpp;
use App\Services\SantriDetailPresenter;

class DashboardController extends Controller
{
    public function index()
    {
        $wali = auth()->user();

        $anakList = $wali->anakSantri()
            ->with(['pesantren', 'kelas', 'kamar', 'pembimbing'])
            ->where('status_aktif', true)
            ->get();

        $santri = null;
        $detail = null;
        $cards  = collect();

        if ($anakList->count() === 1) {
            $santri = $anakList->first();
            $detail = SantriDetailPresenter::detail($santri);
            $statusKesehatanList = collect([[
                'santri'          => $santri,
                'statusKesehatan' => $detail['statusKesehatanTerkini'],
            ]]);
        } else {
            $cards = $anakList->map(fn ($s) => array_merge(
                ['santri' => $s],
                SantriDetailPresenter::cardSummary($s)
            ));
            $statusKesehatanList = $cards;
        }

        // Alert kesehatan lintas anak
        $alertKesehatan = $statusKesehatanList
            ->filter(fn ($c) => in_array($c['statusKesehatan']['status_pemulihan'] ?? null, ['Istirahat_Total', 'Rujukan_Luar']))
            ->map(fn ($c) => [
                'nama'            => $c['santri']->nama_lengkap,
                'status'          => $c['statusKesehatan']['status_pemulihan'],
                'tanggal_periksa' => $c['statusKesehatan']['tanggal_periksa'],
            ]);

        $pengumuman = MasterPengumuman::where('pesantren_id', $wali->pesantren_id)
            ->forWali()->latest()->limit(5)->get();

        $pengumumanCentral = MasterPengumuman::withoutGlobalScope('pesantren')
            ->whereNull('pesantren_id')
            ->forWali()->latest()->limit(3)->get();

        $santriIds = $anakList->pluck('id');
        $tunggakanSpp = TagihanSpp::withoutGlobalScope('pesantren')
            ->whereIn('santri_id', $santriIds)
            ->where('status', StatusTagihanSpp::BelumBayar)
            ->count();

        return view('wali.dashboard', compact(
            'wali',
            'santri',
            'detail',
            'cards',
            'alertKesehatan',
            'pengumuman',
            'pengumumanCentral',
            'tunggakanSpp',
        ));
    }
}
