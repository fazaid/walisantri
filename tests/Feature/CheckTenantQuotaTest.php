<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CheckTenantQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register a minimal route protected by the middleware under test.
        // This route is NOT in the web group, so SaaSLifecycleLock only runs
        // if explicitly listed — keeping each middleware test independent.
        // Path mengandung 'santri' agar lolos filter URL di CheckTenantQuota
        Route::post('/santri/test-quota', fn() => response('ok', 200))
            ->middleware(['auth', 'tenant.quota']);
    }

    private function makePesantren(int $maxKuota): Pesantren
    {
        return Pesantren::create([
            'nama_pesantren'      => 'Pesantren Quota Test',
            'slug'                => 'pesantren-quota-test',
            'paket_langganan'     => 'rintisan',
            'max_santri_kuota'    => $maxKuota,
            'status_berlangganan' => 'active',
            'expired_at'          => now()->addYear(),
        ]);
    }

    private function makeUser(Pesantren $pesantren, string $role, string $suffix): User
    {
        return User::create([
            'pesantren_id' => $pesantren->id,
            'name'         => "{$role} {$suffix}",
            'email'        => strtolower(str_replace('_', '', $role)) . ".{$suffix}@quota.test",
            'password'     => bcrypt('password'),
            'role'         => $role,
        ]);
    }

    private function makeSantri(Pesantren $pesantren, User $wali, User $ustadz, string $nis): Santri
    {
        return Santri::create([
            'pesantren_id'         => $pesantren->id,
            'wali_santri_id'       => $wali->id,
            'pembimbing_ustadz_id' => $ustadz->id,
            'nis'                  => $nis,
            'nama_lengkap'         => "Santri {$nis}",
            'kelas'                => '1A',
            'kamar'                => 'A',
        ]);
    }

    public function test_middleware_memblokir_saat_kuota_penuh(): void
    {
        $pesantren = $this->makePesantren(maxKuota: 2);
        $admin     = $this->makeUser($pesantren, 'admin_pesantren', '1');
        $wali      = $this->makeUser($pesantren, 'wali_santri', '1');
        $ustadz    = $this->makeUser($pesantren, 'ustadz', '1');

        // Fill quota to the max (2 santri, max_santri_kuota = 2).
        $this->makeSantri($pesantren, $wali, $ustadz, 'S001');
        $this->makeSantri($pesantren, $wali, $ustadz, 'S002');

        $this->actingAs($admin)
            ->postJson('/santri/test-quota')
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $msg) => str_contains($msg, 'Batas kuota paket tercapai'));
    }

    public function test_middleware_mengizinkan_saat_kuota_belum_penuh(): void
    {
        $pesantren = $this->makePesantren(maxKuota: 5);
        $admin     = $this->makeUser($pesantren, 'admin_pesantren', '1');
        $wali      = $this->makeUser($pesantren, 'wali_santri', '1');
        $ustadz    = $this->makeUser($pesantren, 'ustadz', '1');

        // Only 2 of 5 slots used → quota not full.
        $this->makeSantri($pesantren, $wali, $ustadz, 'S001');
        $this->makeSantri($pesantren, $wali, $ustadz, 'S002');

        $this->actingAs($admin)
            ->postJson('/santri/test-quota')
            ->assertStatus(200);
    }
}
