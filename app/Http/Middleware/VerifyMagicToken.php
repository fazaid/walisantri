<?php

// File: app/Http/Middleware/VerifyMagicToken.php
// php artisan make:middleware VerifyMagicToken

namespace App\Http\Middleware;

use App\Models\Santri;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyMagicToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $uuid = $request->route('uuid');

        if (! $uuid) {
            abort(400, 'Token tidak valid.');
        }

        // Cari santri berdasarkan UUID — withoutGlobalScope karena belum ada auth
        $santri = Santri::withoutGlobalScope('pesantren')
            ->where('uuid', $uuid)
            ->where('status_aktif', true)
            ->first();

        if (! $santri) {
            abort(404, 'Tautan tidak valid atau santri tidak aktif.');
        }

        // Jika sudah login sebagai admin/ustadz/super_admin, jangan timpa sesi mereka
        if (Auth::check() && Auth::user()->role !== 'wali_santri') {
            return redirect("/admin/santris/{$santri->id}");
        }

        // Login sebagai wali santri — read-only session
        $wali = $santri->wali;

        if (! $wali) {
            abort(404, 'Data wali santri tidak ditemukan.');
        }

        // Login tanpa remember — sesi sementara
        Auth::login($wali, remember: false);

        // Tandai sebagai magic-link session — dipakai untuk block mutasi
        session(['magic_link_session' => true, 'magic_link_santri_id' => $santri->id]);

        // Abort semua request non-GET dari magic link session
        if (! $request->isMethod('GET')) {
            abort(403, 'Akses magic link hanya diizinkan untuk membaca data.');
        }

        return $next($request);
    }
}
