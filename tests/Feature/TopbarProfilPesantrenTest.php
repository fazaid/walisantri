<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Topbar panel: pencarian global ditukar tautan profil pesantren.
 *
 * Pencarian bawaan Filament berfungsi, tapi tidak pernah dikurasi — dari 23
 * resource yang ikut terindeks, lima dicari lewat kolom yang tak berarti bagi
 * manusia (`id`, `tanggal`, `jam_ke`), sehingga mengetik angka memunculkan hasil
 * sampah. Keputusan pemilik produk: ditukar dengan pintu ke halaman yang justru
 * sering dibuka pengurus.
 */
class TopbarProfilPesantrenTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_melihat_tautan_ke_profil_pesantrennya(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->hostnameTenant($pesantren);

        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Profil Pesantren')
            ->assertSee($pesantren->url('/'), false);
    }

    /**
     * Super admin tidak terikat satu pesantren, jadi tidak ada profil yang bisa
     * ditunjuk — partialnya wajib merender kosong, bukan meledak.
     */
    public function test_super_admin_tidak_melihat_tautan_profil(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Profil Pesantren');
    }

    public function test_pencarian_global_dimatikan(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->hostnameTenant($pesantren);

        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('global-search', false);
    }
}
