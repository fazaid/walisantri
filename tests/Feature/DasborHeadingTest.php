<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\User;
use App\Support\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dasbor dibuka heading yang menyebut peran, bukan kartu "Selamat Datang".
 *
 * AccountWidget bawaan Filament memakan satu baris penuh di paling atas dasbor
 * untuk menampilkan avatar, nama, dan tombol Keluar — ketiganya sudah ada di
 * menu pengguna topbar. Keputusan pemilik produk: kartu itu dilepas, diganti
 * heading per role plus jam WIB berjalan, supaya statistik langsung terlihat
 * tanpa menggulir.
 */
class DasborHeadingTest extends TestCase
{
    use RefreshDatabase;

    private function penggunaTenant(string $peran): User
    {
        $pesantren = Pesantren::factory()->create();
        $this->hostnameTenant($pesantren);

        return User::factory()->{$peran}()->create(['pesantren_id' => $pesantren->id]);
    }

    public function test_admin_pesantren_melihat_heading_dasbor_admin(): void
    {
        $this->actingAs($this->penggunaTenant('adminPesantren'))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dasbor Admin');
    }

    public function test_ustadz_melihat_heading_dasbor_ustadz(): void
    {
        $this->actingAs($this->penggunaTenant('ustadz'))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dasbor Ustadz');
    }

    /** Super admin tidak terikat tenant, jadi diakses langsung tanpa host pesantren. */
    public function test_super_admin_melihat_heading_dasbor_super_admin(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dasbor Super Admin');
    }

    public function test_kartu_selamat_datang_bawaan_sudah_hilang(): void
    {
        $this->actingAs($this->penggunaTenant('adminPesantren'))
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Selamat Datang');
    }

    /**
     * Jamnya dirender server lebih dulu supaya benar sejak paint pertama, dan
     * memakai kalender WIB — bukan `now()` mentah yang masih tanggal kemarin
     * antara 00.00–07.00 WIB.
     */
    public function test_waktu_wib_tampil_di_bawah_heading(): void
    {
        $this->actingAs($this->penggunaTenant('adminPesantren'))
            ->get('/admin')
            ->assertOk()
            ->assertSee(Waktu::sekarang()->translatedFormat('l, d F Y').' ·', false)
            ->assertSee('WIB');
    }
}
