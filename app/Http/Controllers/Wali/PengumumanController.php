<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\MasterPengumuman;

class PengumumanController extends Controller
{
    public function index()
    {
        // Pengumuman dari pesantren sendiri untuk wali (hanya target wali/semua)
        $pengumumanPesantren = MasterPengumuman::where(
            'pesantren_id', auth()->user()->pesantren_id
        )
            ->forWali()
            ->latest()
            ->get();

        // Pengumuman global dari Super Admin (pesantren_id = null, target wali/semua)
        $pengumumanCentral = MasterPengumuman::withoutGlobalScope('pesantren')
            ->whereNull('pesantren_id')
            ->forWali()
            ->latest()
            ->get();

        return view('wali.pengumuman', compact('pengumumanPesantren', 'pengumumanCentral'));
    }
}
