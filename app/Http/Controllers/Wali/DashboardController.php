<?php

// File: app/Http/Controllers/Wali/DashboardController.php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\MasterPengumuman;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $wali = auth()->user();

        $anakList = $wali->anakSantri()
            ->with(['pesantren', 'kelas', 'kamar'])
            ->where('status_aktif', true)
            ->get();

        $pengumuman = MasterPengumuman::where('pesantren_id', $wali->pesantren_id)
            ->forWali()
            ->latest()
            ->limit(5)
            ->get();

        $pengumumanCentral = MasterPengumuman::withoutGlobalScope('pesantren')
            ->whereNull('pesantren_id')
            ->forWali()
            ->latest()
            ->limit(3)
            ->get();

        return view('wali.dashboard', compact(
            'wali',
            'anakList',
            'pengumuman',
            'pengumumanCentral',
        ));
    }
}
