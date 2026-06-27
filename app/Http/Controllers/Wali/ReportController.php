<?php

// File: app/Http/Controllers/Wali/ReportController.php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Services\SantriDetailPresenter;

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

    private function buildPayload(Santri $santri): array
    {
        return array_merge(['santri' => $santri], SantriDetailPresenter::detail($santri));
    }
}
