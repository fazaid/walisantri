<?php

namespace Tests\Feature;

use App\Filament\Resources\MasterPengumumen\MasterPengumumanResource;
use App\Filament\Resources\MasterPengumumen\Pages\ListMasterPengumumen;
use App\Models\MasterPengumuman;
use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ustadz punya akses BACA ke pengumuman (karena itu menunya tampil), tapi tidak
 * boleh membuatnya. Filament hanya menegakkan canCreate() di halaman
 * CreateRecord, sehingga tombol "Buat" di daftar harus dijaga manual — kalau
 * tidak, tombolnya tampil lalu berujung 403.
 */
class PengumumanAksesUstadzTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();

        MasterPengumuman::create([
            'pesantren_id' => $this->pesantren->id,
            'judul_maklumat' => 'Pengumuman Tenant',
            'isi_maklumat' => 'Isi pengumuman.',
        ]);
    }

    private function ustadz(): User
    {
        return User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);
    }

    private function adminPesantren(): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);
    }

    public function test_ustadz_bisa_membaca_daftar_pengumuman(): void
    {
        $this->actingAs($this->ustadz())
            ->get(MasterPengumumanResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Pengumuman Tenant');
    }

    public function test_ustadz_tidak_melihat_tombol_buat_pengumuman(): void
    {
        $html = Livewire::actingAs($this->ustadz())
            ->test(ListMasterPengumumen::class)
            ->html();

        $this->assertStringNotContainsString(
            MasterPengumumanResource::getUrl('create'),
            $html,
            'Tombol buat tidak boleh dirender untuk ustadz — link-nya berujung 403.',
        );
    }

    public function test_admin_pesantren_tetap_melihat_tombol_buat_pengumuman(): void
    {
        $html = Livewire::actingAs($this->adminPesantren())
            ->test(ListMasterPengumumen::class)
            ->html();

        $this->assertStringContainsString(MasterPengumumanResource::getUrl('create'), $html);
    }

    public function test_ustadz_ditolak_saat_membuka_halaman_buat_langsung(): void
    {
        // Penjaga di UI hanya kosmetik; otorisasi sebenarnya tetap harus menutup
        // akses lewat URL langsung.
        $this->actingAs($this->ustadz())
            ->get(MasterPengumumanResource::getUrl('create'))
            ->assertForbidden();
    }
}
