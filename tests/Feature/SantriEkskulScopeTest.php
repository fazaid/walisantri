<?php

namespace Tests\Feature;

use App\Filament\Resources\SantriEkskuls\SantriEkskulResource;
use App\Models\EkskulMaster;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\SantriEkskul;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ekskul Santri sempat menjadi satu-satunya resource ber-HasAdminUstadzAccess
 * tanpa pembatasan query, sehingga seorang ustadz melihat data ekskul seluruh
 * santri se-pesantren.
 */
class SantriEkskulScopeTest extends TestCase
{
    use RefreshDatabase;

    private function buatEkskulUntukSantriBimbingan(Pesantren $pesantren, ?User $pembimbing): SantriEkskul
    {
        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'pembimbing_ustadz_id' => $pembimbing?->id,
            'status_aktif' => true,
        ]);

        $ekskul = EkskulMaster::create([
            'pesantren_id' => $pesantren->id,
            'nama' => 'Panahan '.$santri->id,
            'aktif' => true,
        ]);

        return SantriEkskul::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'ekskul_id' => $ekskul->id,
            'level' => 'pemula',
            'tanggal_mulai' => now()->subMonth(),
            'aktif' => true,
        ]);
    }

    public function test_ustadz_hanya_melihat_ekskul_santri_bimbingannya(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadzA = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $ustadzB = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $milikA = $this->buatEkskulUntukSantriBimbingan($pesantren, $ustadzA);
        $milikB = $this->buatEkskulUntukSantriBimbingan($pesantren, $ustadzB);

        $this->actingAs($ustadzA);

        $hasil = SantriEkskulResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($hasil->contains($milikA->id));
        $this->assertFalse($hasil->contains($milikB->id));
    }

    public function test_ustadz_tidak_bisa_membuka_record_ustadz_lain_lewat_url(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadzA = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $ustadzB = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $milikA = $this->buatEkskulUntukSantriBimbingan($pesantren, $ustadzA);
        $milikB = $this->buatEkskulUntukSantriBimbingan($pesantren, $ustadzB);

        $this->actingAs($ustadzA);

        // Inilah jalur yang dipakai Filament saat URL /{record}/edit dibuka.
        $this->assertNotNull(SantriEkskulResource::resolveRecordRouteBinding($milikA->id));
        $this->assertNull(SantriEkskulResource::resolveRecordRouteBinding($milikB->id));
    }

    public function test_admin_pesantren_melihat_seluruh_ekskul_di_pesantrennya(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $milikUstadz = $this->buatEkskulUntukSantriBimbingan($pesantren, $ustadz);
        $tanpaPembimbing = $this->buatEkskulUntukSantriBimbingan($pesantren, null);

        $this->actingAs($admin);

        $hasil = SantriEkskulResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($hasil->contains($milikUstadz->id));
        $this->assertTrue($hasil->contains($tanpaPembimbing->id));
    }
}
