<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DataSantriExport;
use App\Exports\MutabaahBulananExport;
use App\Exports\PresensiRekapExport;
use App\Exports\RekamMedisExport;
use App\Http\Controllers\Controller;
use App\Services\TahunAjaranOptions;
use App\Support\Waktu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function santri()
    {
        abort_unless(Auth::user()?->role === 'admin_pesantren', 403);

        return Excel::download(
            new DataSantriExport(Auth::user()->pesantren_id),
            'data-santri-'.Waktu::sekarang()->format('Y-m-d').'.xlsx',
        );
    }

    public function mutabaah(Request $request)
    {
        abort_unless(in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']), 403);

        $bulan = $request->integer('bulan', Waktu::sekarang()->month);
        $tahun = $request->integer('tahun', Waktu::sekarang()->year);
        $ustadzId = Auth::user()?->role === 'ustadz' ? Auth::id() : null;

        return Excel::download(
            new MutabaahBulananExport(Auth::user()->pesantren_id, $bulan, $tahun, $ustadzId),
            sprintf('mutabaah-%d-%02d.xlsx', $tahun, $bulan),
        );
    }

    public function presensi(Request $request)
    {
        abort_unless(in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']), 403);

        // Ustadz hanya mengekspor kelas perwaliannya — cakupan yang sama dengan
        // halaman Rekap dan Isi Presensi, diteruskan ke service alih-alih
        // diulang di sini.
        $ustadzId = Auth::user()?->role === 'ustadz' ? Auth::id() : null;

        $tahunAjaran = $request->get('tahun_ajaran') ?: TahunAjaranOptions::current();
        $periode = $request->get('periode') ?: TahunAjaranOptions::currentPeriode();

        return Excel::download(
            new PresensiRekapExport(
                Auth::user()->pesantren_id,
                $tahunAjaran,
                $periode,
                $request->get('bulan'),
                $request->integer('kelas_id') ?: null,
                $ustadzId,
            ),
            'rekap-presensi-'.str_replace('/', '-', $tahunAjaran).'-'.strtolower($periode).'.xlsx',
        );
    }

    public function rekamMedis(Request $request)
    {
        abort_unless(in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']), 403);

        $ustadzId = Auth::user()?->role === 'ustadz' ? Auth::id() : null;

        return Excel::download(
            new RekamMedisExport(
                Auth::user()->pesantren_id,
                $request->get('dari'),
                $request->get('sampai'),
                $ustadzId,
            ),
            'rekam-medis-'.Waktu::sekarang()->format('Y-m-d').'.xlsx',
        );
    }
}
