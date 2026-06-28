<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianInventaris;

class InventarisController extends Controller
{
    public function show(int $santriId)
    {
        $wali   = auth()->user();
        $santri = $wali->anakSantri()->with(['kelas', 'kamar'])->findOrFail($santriId);

        $inventaris = KesantrianInventaris::where('santri_id', $santri->id)
            ->orderBy('nama_barang_umum')
            ->get();

        return view('wali.santri.inventaris', compact('santri', 'inventaris'));
    }
}
