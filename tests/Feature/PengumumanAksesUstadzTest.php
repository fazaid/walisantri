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
 * boleh membuatnya. Filament tidak menyembunyikan tombol "Buat" berdasarkan
 * canCreate(), sehingga tombol di daftar harus dijaga manual — kalau tidak,
 * tombolnya tampil lalu modalnya berujung ditolak.
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
        Livewire::actingAs($this->ustadz())
            ->test(ListMasterPengumumen::class)
            ->assertActionHidden('create');
    }

    public function test_admin_pesantren_tetap_melihat_tombol_buat_pengumuman(): void
    {
        Livewire::actingAs($this->adminPesantren())
            ->test(ListMasterPengumumen::class)
            ->assertActionVisible('create');
    }

    public function test_ustadz_tetap_ditolak_meski_aksi_buat_dipanggil_langsung(): void
    {
        // Penjaga ->visible() bukan sekadar kosmetik: Filament menolak me-mount
        // aksi yang tersembunyi (isDisabled() ikut true saat hidden), jadi jalur
        // pemanggilan langsung tanpa lewat tombol pun buntu.
        $ustadz = $this->ustadz();

        $this->actingAs($ustadz);
        $this->assertFalse(MasterPengumumanResource::canCreate());

        Livewire::actingAs($ustadz)
            ->test(ListMasterPengumumen::class)
            ->mountAction('create')
            ->assertActionNotMounted();

        $this->assertSame(1, MasterPengumuman::count());
    }

    public function test_ustadz_tidak_bisa_mengubah_atau_menghapus_pengumuman(): void
    {
        // Aksi tabel Filament tidak diotorisasi otomatis, dan halaman Edit yang dulu
        // menegakkan canEdit() sudah dihapus — tes ini mengunci penjaga penggantinya.
        $pengumuman = MasterPengumuman::first();

        Livewire::actingAs($this->ustadz())
            ->test(ListMasterPengumumen::class)
            ->assertTableActionHidden('edit', $pengumuman)
            ->assertTableActionHidden('delete', $pengumuman);
    }

    public function test_admin_pesantren_bisa_mengubah_pengumuman_pesantrennya(): void
    {
        $pengumuman = MasterPengumuman::first();

        Livewire::actingAs($this->adminPesantren())
            ->test(ListMasterPengumumen::class)
            ->assertTableActionVisible('edit', $pengumuman)
            ->assertTableActionVisible('delete', $pengumuman);
    }

    public function test_admin_pesantren_tidak_bisa_mengubah_pengumuman_global(): void
    {
        $global = MasterPengumuman::create([
            'pesantren_id' => null,
            'judul_maklumat' => 'Pengumuman Platform',
            'isi_maklumat' => 'Broadcast global.',
        ]);

        Livewire::actingAs($this->adminPesantren())
            ->test(ListMasterPengumumen::class)
            ->assertTableActionHidden('edit', $global)
            ->assertTableActionHidden('delete', $global);
    }
}
