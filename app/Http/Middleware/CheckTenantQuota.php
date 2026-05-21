<?php

namespace App\Http\Middleware;

use App\Models\Santri;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckTenantQuota
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Guard: skip jika belum login, super_admin, atau bukan POST/PUT
        if (!$user) {
            return $next($request);
        }

        if ($user->role === 'super_admin') {
            return $next($request);
        }

        if (!$request->isMethod('POST') && !$request->isMethod('PUT')) {
            return $next($request);
        }

        if (!str_contains($request->path(), 'santri')) {
            return $next($request);
        }

        // Guard: skip jika pesantren_id null
        if (!$user->pesantren_id) {
            return $next($request);
        }

        $pesantren = $user->pesantren;

        // Guard: skip jika relasi pesantren tidak ditemukan
        if (!$pesantren) {
            return $next($request);
        }

        $santriAktif = Santri::where('pesantren_id', $pesantren->id)
            ->where('status_aktif', true)
            ->count();

        $kuota = $pesantren->max_santri_kuota;

        if ($santriAktif >= $kuota) {
            if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
                return response()->json([
                    'message' => 'Batas kuota paket tercapai! ' .
                        'Akun aktif Anda saat ini adalah ' . $santriAktif .
                        ' santri. Silakan upgrade kuota melalui menu Billing.',
                ], 422);
            }

            return back()->withErrors([
                'quota' => 'Batas kuota paket tercapai! ' .
                    'Akun aktif Anda saat ini adalah ' . $santriAktif .
                    ' santri (maks. ' . $kuota . '). ' .
                    'Silakan upgrade kuota melalui menu Billing.',
            ]);
        }

        return $next($request);
    }
}
