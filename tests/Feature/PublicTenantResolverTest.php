<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\TenantDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTenantResolverTest extends TestCase
{
    use RefreshDatabase;

    /** Subdomain publik mengikuti pola route `{slug}.` . config('app.base_domain'). */
    private function hostFor(string $slug): string
    {
        return $slug . '.' . config('app.base_domain');
    }

    public function test_hostname_tidak_terdaftar_mengembalikan_404(): void
    {
        $this->get('http://' . $this->hostFor('tidak-terdaftar') . '/')
            ->assertNotFound();
    }

    public function test_hostname_terdaftar_resolve_ke_pesantren_dan_merender_profil(): void
    {
        $pesantren = Pesantren::factory()->create([
            'nama_pesantren' => 'Pesantren Al-Hidayah',
            'slug'           => 'al-hidayah',
            'profil'         => ['deskripsi' => 'Pesantren tahfidz Al-Quran'],
        ]);

        TenantDomain::create([
            'pesantren_id' => $pesantren->id,
            'hostname'     => $this->hostFor('al-hidayah'),
            'type'         => 'subdomain',
            'is_primary'   => true,
            'ssl_status'   => 'active',
        ]);

        $this->get('http://' . $this->hostFor('al-hidayah') . '/')
            ->assertOk()
            ->assertSee('Pesantren Al-Hidayah')
            ->assertViewHas('pesantren', fn (Pesantren $viewPesantren) => $viewPesantren->is($pesantren));
    }

    public function test_request_attribute_pesantren_id_diisi_sesuai_tenant_domain(): void
    {
        $pesantren = Pesantren::factory()->create(['slug' => 'darul-ilmi']);

        TenantDomain::create([
            'pesantren_id' => $pesantren->id,
            'hostname'     => $this->hostFor('darul-ilmi'),
            'type'         => 'subdomain',
            'is_primary'   => true,
            'ssl_status'   => 'active',
        ]);

        $this->get('http://' . $this->hostFor('darul-ilmi') . '/pengumuman')
            ->assertOk()
            ->assertViewHas('pesantren', fn (Pesantren $viewPesantren) => $viewPesantren->id === $pesantren->id);
    }
}
