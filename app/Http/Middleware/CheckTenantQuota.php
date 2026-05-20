<?php

// File: app/Http/Middleware/CheckTenantQuota.php
// php artisan make:middleware CheckTenantQuota

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantQuota
{
    public function handle(Request $request, Closure $next): Response
    {
        $pesantren = auth()->user()->pesantren;

        if ($pesantren && $pesantren->isQuotaFull()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Kuota santri aktif telah penuh. Upgrade paket untuk menambah santri.',
                ], 422);
            }

            return redirect()->back()->withErrors([
                'quota' => 'Kuota santri aktif telah penuh ('
                    . $pesantren->max_santri_kuota
                    . ' santri). Silakan upgrade paket langganan.',
            ]);
        }

        return $next($request);
    }
}
