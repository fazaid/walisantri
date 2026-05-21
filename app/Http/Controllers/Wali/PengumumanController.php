<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\MasterPengumuman;

class PengumumanController extends Controller
{
    public function index()
    {
        // Pengumuman dari pesantren sendiri (Multitenantable auto-scope ke pesantren_id wali)
        $pengumumanPesantren = MasterPengumuman::where(
            'pesantren_id', auth()->user()->pesantren_id
        )->latest()->get();

        // Pengumuman central dari Super Admin (pesantren_id = null)
        // Bypass Multitenantable scope agar baris global (pesantren_id IS NULL) ikut tampil
        $pengumumanCentral = MasterPengumuman::withoutGlobalScope('pesantren')
            ->whereNull('pesantren_id')
            ->latest()
            ->get();

        return view('wali.pengumuman', compact('pengumumanPesantren', 'pengumumanCentral'));
    }
}
