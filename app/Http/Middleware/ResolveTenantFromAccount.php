<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromAccount
{
    /**
     * Setelah auth berhasil, inject konteks tenant dari akun (bukan dari host).
     * Email unik global → pesantren_id → app()->instance('current_pesantren').
     * Juga set SET app.current_pesantren untuk PostgreSQL RLS (§1.3).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $pesantren = $user->pesantren;

        if ($pesantren) {
            app()->instance('current_pesantren', $pesantren);

            // Rute wali hidup di grup domain berparameter ({slug}.walisantri.com,
            // §1.8 Fase 1), jadi setiap route('wali.*') butuh slug. Default ini
            // menutup konteks yang TIDAK berjalan di host tenant tapi tetap
            // merender view wali: preview admin (admin.preview.wali) dan ekspor
            // dari panel. Tanpa ini keduanya mati dengan "Missing required
            // parameter" — dan matinya di 38 call site sekaligus.
            URL::defaults(['slug' => $pesantren->slug]);

            // SET app.current_pesantren untuk RLS (opsional, aktifkan saat RLS ready §1.1)
            // DB::statement("SET app.current_pesantren = {$pesantren->id}");
        }

        return $next($request);
    }
}
