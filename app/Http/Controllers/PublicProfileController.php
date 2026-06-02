<?php

namespace App\Http\Controllers;

use App\Models\MasterPengumuman;
use App\Models\Pesantren;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    public function index(Request $request)
    {
        /** @var Pesantren $pesantren */
        $pesantren = $request->attributes->get('public_pesantren');

        // Feed pengumuman publik — tanpa sentuh data santri (§1.4)
        $pengumuman = MasterPengumuman::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)
            ->where(function ($q) {
                $q->where('target_audience', 'semua')
                  ->orWhere('target_audience', 'wali');
            })
            ->latest()
            ->limit(10)
            ->get();

        $loginUrl = route('login') . '?tenant=' . $pesantren->slug;

        return view('public.profile', compact('pesantren', 'pengumuman', 'loginUrl'));
    }

    public function pengumuman(Request $request)
    {
        /** @var Pesantren $pesantren */
        $pesantren = $request->attributes->get('public_pesantren');

        $pengumuman = MasterPengumuman::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)
            ->where(function ($q) {
                $q->where('target_audience', 'semua')
                  ->orWhere('target_audience', 'wali');
            })
            ->latest()
            ->paginate(15);

        $loginUrl = route('login') . '?tenant=' . $pesantren->slug;

        return view('public.pengumuman', compact('pesantren', 'pengumuman', 'loginUrl'));
    }
}
