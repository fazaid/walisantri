<?php

// File: app/Http/Middleware/BlockMagicLinkSession.php
// php artisan make:middleware BlockMagicLinkSession

namespace App\Http\Middleware;

use App\Models\Santri;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tegakkan batas read-only untuk sesi magic link pada grup route `wali.*`.
 *
 * VerifyMagicToken menjalankan Auth::login($wali), jadi sesi magic link secara
 * teknis adalah sesi wali penuh. Tanpa penjagaan ini, pemegang magic link bisa
 * menebak URL `wali.*` lain (dashboard, spp, konfirmasi POST) karena grup itu
 * hanya memakai middleware `auth`. Menyembunyikan navigasi di layout hanyalah
 * kosmetik — kontrol akses yang sebenarnya ditegakkan di sini.
 *
 * Sesi magic link boleh MEMBACA report santrinya beserta halaman detail/statistik
 * yang ditaut dari report itu (tahfidz, kesehatan, mutabaah, inventaris), tapi:
 *   - semua mutasi (non-GET) diblok, dan
 *   - hanya untuk santri milik tautan ini (tidak bisa menebak id santri lain),
 *   - halaman portal agregat (dashboard, spp, pengumuman, rapor, uang-saku) —
 *     yang navigasinya memang disembunyikan di layout — dialihkan ke report.
 */
class BlockMagicLinkSession
{
    /**
     * Route `wali.*` yang boleh dibaca dari sesi magic link.
     * Ini persis halaman report + detail/statistik yang ditaut dari report.
     */
    private const ROUTE_DIIZINKAN = [
        'wali.santri.show',
        'wali.santri.tahfidz',
        'wali.santri.kesehatan',
        'wali.santri.mutabaah',
        'wali.santri.inventaris',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Sesi login normal (tanpa flag) lolos tanpa perubahan.
        if (! $request->session()->get('magic_link_session')) {
            return $next($request);
        }

        // Mutasi tidak pernah diizinkan dari magic link.
        if (! $request->isMethod('GET')) {
            abort(403, 'Akses magic link hanya diizinkan untuk membaca data.');
        }

        $santriId = $request->session()->get('magic_link_santri_id');
        $routeName = $request->route()?->getName();

        // Route detail/statistik yang diizinkan — tapi hanya untuk santri milik
        // tautan ini. Menebak id santri lain dialihkan ke report yang benar.
        if (in_array($routeName, self::ROUTE_DIIZINKAN, true)) {
            $santriParam = $request->route('santri');

            if ($santriParam === null || (string) $santriParam === (string) $santriId) {
                return $next($request);
            }
        }

        // Selain itu (halaman portal agregat / santri lain) → kembali ke report.
        $santri = $santriId
            ? Santri::withoutGlobalScope('pesantren')->find($santriId)
            : null;

        if ($santri) {
            return redirect()->route('wali.magic.report', $santri->uuid);
        }

        // Data sesi tidak lengkap/rusak — hentikan sesi sementara ini.
        abort(403, 'Tautan tidak valid.');
    }
}
