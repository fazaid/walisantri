<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\MasterPengumuman;

class PengumumanController extends Controller
{
    public function index()
    {
        // Pengumuman dari pesantren sendiri + broadcast global super admin (hanya target wali/semua)
        $pengumumanPesantren = MasterPengumuman::where(function ($query) {
                $query->where('pesantren_id', auth()->user()->pesantren_id)
                    ->orWhereNull('pesantren_id');
            })
            ->forWali()
            ->latest()
            ->get();

        return view('wali.pengumuman', compact('pengumumanPesantren'));
    }
}
