<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

            // SET app.current_pesantren untuk RLS (opsional, aktifkan saat RLS ready §1.1)
            // DB::statement("SET app.current_pesantren = {$pesantren->id}");
        }

        return $next($request);
    }
}
