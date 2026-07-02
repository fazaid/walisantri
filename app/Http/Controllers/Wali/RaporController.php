<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianKarakterRapor;
use App\Models\NilaiAkademik;
use App\Models\TahfidzUjian;
use App\Services\TahunAjaranOptions;

class RaporController extends Controller
{
    public function index()
    {
        $wali = auth()->user();

        $anakList = $wali->anakSantri()->where('status_aktif', true)->get();

        $santriId    = request('santri_id', $anakList->first()?->id);
        $tahunAjaran = request('tahun_ajaran', TahunAjaranOptions::current());

        $raporTahfidz = TahfidzUjian::where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->orderBy('periode')
            ->get();

        // Filter karakter rapor berdasarkan tahun (4 digit pertama tahun_ajaran)
        $raporKarakter = KesantrianKarakterRapor::where('santri_id', $santriId)
            ->where('tanggal_input', 'like', substr($tahunAjaran, 0, 4) . '%')
            ->latest('tanggal_input')
            ->first();

        $raporAkademik = NilaiAkademik::with('mataPelajaran')
            ->where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->get()
            ->groupBy('periode');

        $tahunList = TahfidzUjian::where('santri_id', $santriId)
            ->distinct()
            ->orderByDesc('tahun_ajaran')
            ->pluck('tahun_ajaran');

        return view('wali.rapor', compact(
            'anakList',
            'santriId',
            'tahunAjaran',
            'tahunList',
            'raporTahfidz',
            'raporKarakter',
            'raporAkademik',
        ));
    }
}
