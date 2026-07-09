<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wali\Concerns\ResolvesSantriMilikWali;
use App\Models\Santri;
use App\Models\UangSakuSantri;

class UangSakuController extends Controller
{
    use ResolvesSantriMilikWali;

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
        $this->pastikanSantriMilikWali($santri->id);

        $transaksi = UangSakuSantri::withoutGlobalScope('pesantren')
            ->where('santri_id', $santri->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $saldo = UangSakuSantri::getSaldo($santri->id);

        return view('wali.uang-saku.show', compact('santri', 'transaksi', 'saldo'));
    }
}
