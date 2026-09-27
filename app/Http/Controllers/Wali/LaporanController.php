<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianKarakterRapor;
use App\Models\NilaiAkademik;
use App\Models\Santri;
use App\Models\TahfidzProgress;
use App\Models\TahfidzUjian;
use App\Services\Rapor\RaporMutabaahData;
use App\Services\Rapor\RaporPresensiData;
use App\Services\TahunAjaranOptions;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function exportPdf()
    {
        $santriId = request('santri_id');
        $tahunAjaran = request('tahun_ajaran', TahunAjaranOptions::current());

        // Validasi: santri harus milik wali yang sedang login
        $santri = Santri::where('id', $santriId)
            ->where('wali_santri_id', auth()->id())
            ->with(['pesantren', 'kelas', 'kamar'])
            ->firstOrFail();

        // Cakupan PDF sengaja satu tahun ajaran penuh — sama dengan yang tampil di
        // halaman /wali/rapor, yang memang tidak punya filter periode.
        $raporTahfidz = TahfidzUjian::where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->orderBy('periode')
            ->get();

        $raporKarakter = KesantrianKarakterRapor::where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->orderByDesc('tanggal_input')
            ->get();

        $raporAkademik = NilaiAkademik::with('mataPelajaran')
            ->where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->get()
            ->groupBy('periode');

        // Setoran harian tidak punya kolom periode, jadi disaring lewat rentang
        // tanggal tahun ajaran (Juli–Juni), bukan tahun kalender.
        [$awal, $akhir] = TahunAjaranOptions::rentangTanggal($tahunAjaran, 'Tahunan');

        $progressTahfidz = TahfidzProgress::where('santri_id', $santriId)
            ->whereBetween('tanggal', [$awal, $akhir])
            ->latest('tanggal')
            ->take(10)
            ->get();

        // Mutaba'ah dan Presensi dicatat harian tanpa kolom periode, jadi dipakai
        // langsung lewat kelas Rapor\* yang sama dengan PDF panel admin — periode
        // 'Tahunan' diterjemahkan ke rentang tahun ajaran penuh (Juli–Juni), sejalan
        // dengan cakupan tiga rapor lain di atas.
        $raporMutabaah = RaporMutabaahData::untuk($santri->id, $tahunAjaran, 'Tahunan');
        $raporPresensi = RaporPresensiData::untuk($santri->id, $tahunAjaran, 'Tahunan');

        $pdf = Pdf::loadView('wali.pdf.laporan', compact(
            'santri',
            'raporTahfidz',
            'raporKarakter',
            'raporAkademik',
            'raporMutabaah',
            'raporPresensi',
            'progressTahfidz',
            'tahunAjaran',
        ))->setPaper('A4', 'portrait');

        $filename = 'Laporan-'
            .str_replace(' ', '-', $santri->nama_lengkap)
            .'-'.str_replace('/', '-', $tahunAjaran)
            .'.pdf';

        return $pdf->download($filename);
    }
}
