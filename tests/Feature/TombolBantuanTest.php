<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\PlatformBrandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol Bantuan di topbar panel — membuka WhatsApp ke tim.
 *
 * Nomornya setelan platform (Merek & Kontak), bukan hardcode: super admin bisa
 * menggantinya tanpa deploy, dan tombolnya menghilang sendiri saat kosong.
 */
class TombolBantuanTest extends TestCase
{
    use RefreshDatabase;

    private function adminPesantren(): User
    {
        $pesantren = Pesantren::factory()->create();
        $this->hostnameTenant($pesantren);

        return User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
    }

    public function test_tombol_tidak_muncul_saat_nomor_belum_diisi(): void
    {
        $this->actingAs($this->adminPesantren())
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('wa.me', false);
    }

    public function test_tombol_muncul_dan_nomornya_dirapikan(): void
    {
        // Sengaja ditulis apa adanya seperti orang mengetik nomor di form.
        PlatformBrandingSetting::set('wa_dukungan', '0812-3456-7890');

        $this->actingAs($this->adminPesantren())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Bantuan')
            ->assertSee('https://wa.me/6281234567890', false);
    }

    public function test_format_lain_menghasilkan_nomor_yang_sama(): void
    {
        foreach (['+62 812 3456 7890', '62-812-3456-7890', '0812 3456 7890'] as $ditulis) {
            PlatformBrandingSetting::set('wa_dukungan', $ditulis);

            $this->assertSame(
                '6281234567890',
                PlatformBrandingSetting::waDukungan(),
                "Format \"{$ditulis}\" tidak dirapikan ke bentuk wa.me."
            );
        }
    }

    /**
     * Pesan pembukanya menyebut pesantren pengirim — pertanyaan pertama tim
     * dukungan selalu "ini dari pesantren mana".
     */
    public function test_pesan_pembuka_menyebut_pesantren_pengirim(): void
    {
        PlatformBrandingSetting::set('wa_dukungan', '628123456789');

        $admin = $this->adminPesantren();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee(rawurlencode($admin->pesantren->nama_pesantren), false);
    }
}
