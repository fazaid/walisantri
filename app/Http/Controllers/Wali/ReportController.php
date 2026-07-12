<?php

// File: app/Http/Controllers/Wali/ReportController.php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Observers\ActivityLogger;
use App\Services\SantriDetailPresenter;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    // Detail santri — diakses wali yang sudah login normal
    public function show(int $santriId)
    {
        $wali = auth()->user();

        // Pastikan santri ini milik wali yang login
        $santri = $wali->anakSantri()
            ->with(['pembimbing', 'pesantren', 'kelas', 'kamar'])
            ->findOrFail($santriId);

        return view('wali.santri.show', $this->buildPayload($santri));
    }

    // Magic Link — diakses via /report/{uuid}
    public function showByUuid(string $uuid)
    {
        // VerifyMagicToken middleware sudah handle auth & validasi UUID
        $santriId = session('magic_link_santri_id');

        $santri = Santri::withoutGlobalScope('pesantren')
            ->with(['pembimbing', 'pesantren', 'kelas', 'kamar'])
            ->findOrFail($santriId);

        return view('wali.santri.show', $this->buildPayload($santri));
    }

    // Preview admin/ustadz — render tampilan wali tanpa Auth::login (sesi admin tetap utuh)
    public function previewAsAdmin(Santri $santri)
    {
        abort_unless(
            in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz', 'super_admin']),
            403
        );

        $santri->load(['pembimbing', 'pesantren', 'kelas', 'kamar']);

        ActivityLogger::log('wali_preview.viewed', $santri, null, ['viewed_by' => Auth::id()]);

        return view('wali.santri.show', array_merge(
            $this->buildPayload($santri),
            ['previewMode' => true]
        ));
    }

    private function buildPayload(Santri $santri): array
    {
        return array_merge(['santri' => $santri], SantriDetailPresenter::detail($santri));
    }
}
