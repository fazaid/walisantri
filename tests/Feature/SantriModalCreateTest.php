<?php

namespace Tests\Feature;

use App\Filament\Resources\Santris\Pages\ListSantris;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SantriModalCreateTest extends TestCase
{
    use RefreshDatabase;

    private function admin(Pesantren $pesantren): User
    {
        return User::factory()->create([
            'role'         => 'admin_pesantren',
            'pesantren_id' => $pesantren->id,
        ]);
    }

    public function test_admin_bisa_tambah_santri_lewat_modal_di_halaman_list(): void
    {
        $pesantren = Pesantren::factory()->create(['max_santri_kuota' => 10]);
        $this->actingAs($this->admin($pesantren));

        Livewire::test(ListSantris::class)
            ->callAction('create', [
                'nis'          => '20260001',
                'nama_lengkap' => 'Ahmad Fauzi',
                'status_aktif' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('santri', [
            'pesantren_id' => $pesantren->id,
            'nis'          => '20260001',
            'nama_lengkap' => 'Ahmad Fauzi',
        ]);
    }

    public function test_kuota_penuh_menampilkan_notifikasi_dan_santri_tidak_tersimpan(): void
    {
        $pesantren = Pesantren::factory()->create(['max_santri_kuota' => 1]);
        Santri::factory()->create(['pesantren_id' => $pesantren->id, 'status_aktif' => true]);

        $this->actingAs($this->admin($pesantren));

        Livewire::test(ListSantris::class)
            ->callAction('create', [
                'nis'          => '20260002',
                'nama_lengkap' => 'Santri Melebihi Kuota',
                'status_aktif' => true,
            ])
            ->assertNotified('Kuota Santri Penuh');

        $this->assertDatabaseMissing('santri', ['nis' => '20260002']);
    }

    public function test_admin_bisa_edit_santri_lewat_modal_di_tabel(): void
    {
        $pesantren = Pesantren::factory()->create(['max_santri_kuota' => 10]);
        $wali      = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);
        $ustadz    = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $santri    = Santri::factory()->create([
            'pesantren_id'         => $pesantren->id,
            'wali_santri_id'       => $wali->id,
            'pembimbing_ustadz_id' => $ustadz->id,
            'nama_lengkap'         => 'Nama Lama',
        ]);

        $this->actingAs($this->admin($pesantren));

        Livewire::test(ListSantris::class)
            ->callTableAction('edit', $santri, ['nama_lengkap' => 'Nama Baru'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Nama Baru', $santri->fresh()->nama_lengkap);
    }

    public function test_ustadz_tidak_melihat_tombol_tambah_santri(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz    = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($ustadz);

        Livewire::test(ListSantris::class)
            ->assertActionHidden('create');
    }

    public function test_halaman_create_dan_edit_lama_sudah_tidak_ada(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri    = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($this->admin($pesantren));

        $this->get('/admin/santri/santris/create')->assertNotFound();
        $this->get("/admin/santri/santris/{$santri->getRouteKey()}/edit")->assertNotFound();
    }
}
