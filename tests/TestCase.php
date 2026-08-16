<?php

namespace Tests;

use App\Models\Pesantren;
use App\Models\TenantDomain;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    /**
     * URL absolut di host tenant ({slug}.walisantri.test) — §1.8 Fase 1.
     *
     * Sejak portal wali, login, dan magic link pindah ke host pesantren, request
     * tes tidak bisa lagi memakai host default: `PublicTenantResolver` mencocokkan
     * hostname ke `tenant_domains` dan abort(404) untuk host tak dikenal. Helper
     * ini sekalian memastikan baris domainnya ada, karena `PesantrenFactory` hanya
     * membuat barisan `pesantrens` — di produksi baris domain dibuat
     * `ProvisionTenant`, bukan factory.
     */
    protected function urlTenant(Pesantren $pesantren, string $path = '/'): string
    {
        $hostname = $this->hostnameTenant($pesantren);

        return 'http://'.$hostname.'/'.ltrim($path, '/');
    }

    /**
     * Jadikan pesantren ini konteks host untuk seluruh `route('wali.*')` di tes.
     *
     * Menirukan persis yang dilakukan middleware `tenant.host` di produksi: mengisi
     * default parameter `slug`. Dengan ini call site `route('wali.dashboard')` yang
     * sudah ada tidak perlu disentuh sama sekali — ia otomatis menghasilkan URL di
     * host tenant, dan requestnya mendarat di grup rute yang benar.
     */
    protected function pakaiHostTenant(Pesantren $pesantren): string
    {
        $hostname = $this->hostnameTenant($pesantren);

        URL::defaults(['slug' => $pesantren->slug]);

        return $hostname;
    }

    protected function hostnameTenant(Pesantren $pesantren): string
    {
        $domain = TenantDomain::firstOrCreate(
            ['pesantren_id' => $pesantren->id, 'type' => 'subdomain'],
            [
                'hostname' => $pesantren->slug.'.'.config('app.base_domain'),
                'is_primary' => true,
                'ssl_status' => 'active',
            ],
        );

        return $domain->hostname;
    }
}
