<?php

namespace App\Http\Controllers\Wali;

use App\Enums\JenisUangSaku;
use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\UangSakuSantri;

class UangSakuController extends Controller
{
    public function index()
    {
        $wali = auth()->user();

        $santris = $wali->anakSantri()->with(['kelas'])->get();

        $saldoMap = $santris->mapWithKeys(function ($santri) {
            return [$santri->id => UangSakuSantri::getSaldo($santri->id)];
        });

        return view('wali.uang-saku.index', compact('santris', 'saldoMap'));
    }

    public function show(Santri $santri)
    {
        $waliSantriIds = auth()->user()->anakSantri()->pluck('id');
        abort_unless($waliSantriIds->contains($santri->id), 403);

        $transaksi = UangSakuSantri::withoutGlobalScope('pesantren')
            ->where('santri_id', $santri->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $saldo = UangSakuSantri::getSaldo($santri->id);

        return view('wali.uang-saku.show', compact('santri', 'transaksi', 'saldo'));
    }
}
