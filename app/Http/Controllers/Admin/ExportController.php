<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DataSantriExport;
use App\Exports\MutabaahBulananExport;
use App\Exports\RekamMedisExport;
use App\Http\Controllers\Controller;
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
            'data-santri-' . now()->format('Y-m-d') . '.xlsx',
        );
    }

    public function mutabaah(Request $request)
    {
        abort_unless(in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']), 403);

        $bulan    = $request->integer('bulan', now()->month);
        $tahun    = $request->integer('tahun', now()->year);
        $ustadzId = Auth::user()?->role === 'ustadz' ? Auth::id() : null;

        return Excel::download(
            new MutabaahBulananExport(Auth::user()->pesantren_id, $bulan, $tahun, $ustadzId),
            sprintf('mutabaah-%d-%02d.xlsx', $tahun, $bulan),
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
            'rekam-medis-' . now()->format('Y-m-d') . '.xlsx',
        );
    }
}
