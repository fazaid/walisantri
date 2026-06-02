<?php

namespace App\Http\Middleware;

use App\Models\TenantDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicTenantResolver
{
    /**
     * Cocokkan hostname request ke tenant_domains → set pesantren_id di request.
     * Read-only: hanya untuk render situs profil publik (§1.3).
     * Tidak pernah membaca data santri.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hostname = $request->getHost();

        $domain = TenantDomain::where('hostname', $hostname)->first();

        if (! $domain) {
            abort(404);
        }

        $request->attributes->set('public_pesantren_id', $domain->pesantren_id);
        $request->attributes->set('public_pesantren', $domain->pesantren);

        return $next($request);
    }
}
