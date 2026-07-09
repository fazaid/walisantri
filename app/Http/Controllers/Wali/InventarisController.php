<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wali\Concerns\ResolvesSantriMilikWali;
use App\Models\KesantrianInventaris;

class InventarisController extends Controller
{
    use ResolvesSantriMilikWali;

    public function show(int $santriId)
    {
        $santri = $this->santriMilikWali($santriId);

        $inventaris = KesantrianInventaris::where('santri_id', $santri->id)
            ->orderBy('nama_barang_umum')
            ->get();

        return view('wali.santri.inventaris', compact('santri', 'inventaris'));
    }
}
