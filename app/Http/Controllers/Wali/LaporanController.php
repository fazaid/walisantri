<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianKarakterRapor;
use App\Models\NilaiAkademik;
use App\Models\Santri;
use App\Models\TahfidzProgress;
use App\Models\TahfidzUjian;
use App\Services\TahunAjaranOptions;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function exportPdf()
    {
        $santriId    = request('santri_id');
        $periode     = request('periode', TahunAjaranOptions::currentPeriode());
        $tahunAjaran = request('tahun_ajaran', TahunAjaranOptions::current());

        // Validasi: santri harus milik wali yang sedang login
        $santri = Santri::where('id', $santriId)
            ->where('wali_santri_id', auth()->id())
            ->with(['pesantren', 'kelas', 'kamar'])
            ->firstOrFail();

        $raporTahfidz = TahfidzUjian::where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('periode', $periode)
            ->first();

        // Karakter rapor: map periode tahfidz → periode karakter (Bulanan / Semester)
        $periodeKarakter = str_contains($periode, 'Semester') ? 'Semester' : 'Bulanan';

        $raporKarakter = KesantrianKarakterRapor::where('santri_id', $santriId)
            ->where('periode', $periodeKarakter)
            ->latest('tanggal_input')
            ->first();

        $raporAkademik = NilaiAkademik::with('mataPelajaran')
            ->where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('periode', $periode)
            ->get();

        $progressTahfidz = TahfidzProgress::where('santri_id', $santriId)
            ->whereBetween('tanggal', [now()->startOfYear(), now()->endOfYear()])
            ->latest('tanggal')
            ->take(10)
            ->get();

        $pdf = Pdf::loadView('wali.pdf.laporan', compact(
            'santri',
            'raporTahfidz',
            'raporKarakter',
            'raporAkademik',
            'progressTahfidz',
            'tahunAjaran',
            'periode',
        ))->setPaper('A4', 'portrait');

        $filename = 'Laporan-'
            . str_replace(' ', '-', $santri->nama_lengkap)
            . '-' . str_replace('/', '-', $tahunAjaran)
            . '.pdf';

        return $pdf->download($filename);
    }
}
