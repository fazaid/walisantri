<?php

namespace Tests\TenantIsolation;

use App\Enums\Modul;
use App\Filament\Clusters;
use App\Filament\Resources\TagihanSpps\TagihanSppResource;
use App\Models\Kelas;
use App\Models\ModulPengaturan;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §17: Test isolasi tenant wajib pakai PostgreSQL (bukan SQLite).
 * Jalankan via: php artisan test --configuration=phpunit.tenant.xml
 *
 * Toggle modul adalah keputusan SATU pesantren. Memoisasinya di container bertahan
 * sepanjang request, dan kalau kuncinya kelak salah (mis. dipangkas jadi satu kunci
 * global demi "menghemat"), pesantren yang mematikan Keuangan akan mematikannya juga
 * untuk tenant lain yang kebetulan dilayani proses yang sama — tanpa galat apa pun.
 */
class ModulIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped(
                'ModulIsolationTest wajib pakai PostgreSQL. '.
                'Jalankan dengan DB_CONNECTION=pgsql.'
            );
        }
    }

    /** @return array{0: Pesantren, 1: User} */
    private function tenant(string $slug): array
    {
        $pesantren = Pesantren::factory()->create(['slug' => $slug]);
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        return [$pesantren, $admin];
    }

    public function test_modul_yang_dimatikan_tenant_a_tidak_menyentuh_tenant_b(): void
    {
        [$pesantrenA, $adminA] = $this->tenant('modul-a');
        [, $adminB] = $this->tenant('modul-b');

        ModulPengaturan::untuk($pesantrenA->id)->update(['keuangan_aktif' => false]);

        $this->actingAs($adminA);
        $this->assertFalse(TagihanSppResource::canViewAny());
        $this->assertFalse(Clusters\Keuangan::canAccessClusteredComponents());

        $this->actingAs($adminB);
        $this->assertTrue(TagihanSppResource::canViewAny());
        $this->assertTrue(Clusters\Keuangan::canAccessClusteredComponents());
    }

    public function test_memo_pengaturan_tidak_bocor_antar_tenant_dalam_satu_request(): void
    {
        [$pesantrenA] = $this->tenant('memo-a');
        [$pesantrenB] = $this->tenant('memo-b');

        // Dibaca lebih dulu supaya keduanya masuk memo, lalu salah satunya diubah.
        ModulPengaturan::untuk($pesantrenA->id);
        ModulPengaturan::untuk($pesantrenB->id);

        ModulPengaturan::untuk($pesantrenA->id)->update(['tahfidz_aktif' => false]);

        $this->assertFalse(Modul::Tahfidz->aktif($pesantrenA->id));
        $this->assertTrue(Modul::Tahfidz->aktif($pesantrenB->id));
    }

    public function test_portal_wali_tenant_b_tidak_ikut_kehilangan_menu(): void
    {
        [$pesantrenA] = $this->tenant('wali-modul-a');
        [$pesantrenB] = $this->tenant('wali-modul-b');

        ModulPengaturan::untuk($pesantrenA->id)->update(['keuangan_aktif' => false]);

        $waliB = User::factory()->waliSantri()->create(['pesantren_id' => $pesantrenB->id]);
        $kelasB = Kelas::factory()->create(['pesantren_id' => $pesantrenB->id]);
        Santri::factory()->create([
            'pesantren_id' => $pesantrenB->id,
            'wali_santri_id' => $waliB->id,
            'kelas_id' => $kelasB->id,
            'status_aktif' => true,
        ]);

        $this->pakaiHostTenant($pesantrenB);

        $this->actingAs($waliB)
            ->get(route('wali.spp'))
            ->assertOk();
    }
}
