<?php

namespace App\Http\Controllers\Wali;

use App\Enums\StatusTagihanSpp;
use App\Http\Controllers\Controller;

class SppController extends Controller
{
    public function index()
    {
        $wali = auth()->user();

        $santris = $wali->anakSantri()->with([
            'tagihanSpp' => fn ($q) => $q->with('pembayaran')
                ->orderByDesc('tahun')
                ->orderByDesc('bulan'),
        ])->get();

        $totalTunggakan = $santris->sum(
            fn ($s) => $s->tagihanSpp->where('status', StatusTagihanSpp::BelumBayar)->count()
        );

        return view('wali.spp.index', compact('santris', 'totalTunggakan'));
    }
}
