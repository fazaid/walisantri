<?php

namespace Tests\Feature;

use App\Filament\Resources\Kamars\KamarResource;
use App\Filament\Resources\Kamars\Pages\ListKamars;
use App\Filament\Resources\Kelas\KelasResource;
use App\Filament\Resources\Kelas\Pages\ListKelas;
use App\Filament\Resources\PrestasiSantris\Pages\ListPrestasiSantris;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\PrestasiSantri;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class MasterDataModalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(Pesantren $pesantren): User
    {
        return User::factory()->create([
            'role' => 'admin_pesantren',
            'pesantren_id' => $pesantren->id,
        ]);
    }

    // ---------- Kelas ----------

    public function test_admin_bisa_tambah_kelas_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->actingAs($this->admin($pesantren));

        Livewire::test(ListKelas::class)
            ->callAction('create', ['nama_kelas' => 'Ulya 3'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('kelas', [
            'pesantren_id' => $pesantren->id,
            'nama_kelas' => 'Ulya 3',
        ]);
    }

    public function test_admin_bisa_edit_dan_hapus_kelas_lewat_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $kelas = Kelas::factory()->create([
            'pesantren_id' => $pesantren->id,
            'nama_kelas' => 'Nama Lama',
        ]);

        $this->actingAs($this->admin($pesantren));

        Livewire::test(ListKelas::class)
            ->callTableAction('edit', $kelas, ['nama_kelas' => 'Nama Baru'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Nama Baru', $kelas->fresh()->nama_kelas);

        Livewire::test(ListKelas::class)->callTableAction('delete', $kelas);

        $this->assertDatabaseMissing('kelas', ['id' => $kelas->id]);
    }

    // ---------- Kamar ----------

    public function test_admin_bisa_tambah_kamar_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->actingAs($this->admin($pesantren));

        Livewire::test(ListKamars::class)
            ->callAction('create', ['nama_kamar' => 'Kamar Abu Bakar', 'kapasitas' => 20])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('kamar', [
            'pesantren_id' => $pesantren->id,
            'nama_kamar' => 'Kamar Abu Bakar',
            'kapasitas' => 20,
        ]);
    }

    public function test_admin_bisa_edit_dan_hapus_kamar_lewat_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $kamar = Kamar::create([
            'pesantren_id' => $pesantren->id,
            'nama_kamar' => 'Kamar Lama',
            'kapasitas' => 10,
        ]);

        $this->actingAs($this->admin($pesantren));

        Livewire::test(ListKamars::class)
            ->callTableAction('edit', $kamar, ['nama_kamar' => 'Kamar Baru', 'kapasitas' => 15])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Kamar Baru', $kamar->fresh()->nama_kamar);
        $this->assertSame(15, $kamar->fresh()->kapasitas);

        Livewire::test(ListKamars::class)->callTableAction('delete', $kamar);

        $this->assertDatabaseMissing('kamar', ['id' => $kamar->id]);
    }

    // ---------- Prestasi ----------

    private function prestasiData(Santri $santri): array
    {
        return [
            'santri_id' => $santri->id,
            'tanggal' => '2026-08-01',
            'judul' => 'Juara 1 MTQ Cabang Tilawah',
            'kategori' => 'Tilawah Al-Quran',
            'tingkat' => 'kabupaten',
            'posisi' => 'Juara 1',
            'penyelenggara' => 'Kemenag Kab. Bandung',
        ];
    }

    public function test_admin_bisa_tambah_prestasi_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($this->admin($pesantren));

        Livewire::test(ListPrestasiSantris::class)
            ->callAction('create', $this->prestasiData($santri))
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('prestasi_santri', [
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'judul' => 'Juara 1 MTQ Cabang Tilawah',
        ]);
    }

    public function test_admin_bisa_edit_prestasi_lewat_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);
        $prestasi = PrestasiSantri::create([
            'pesantren_id' => $pesantren->id,
            ...$this->prestasiData($santri),
            'judul' => 'Judul Lama',
        ]);

        $this->actingAs($this->admin($pesantren));

        Livewire::test(ListPrestasiSantris::class)
            ->callTableAction('edit', $prestasi, ['judul' => 'Judul Baru'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Judul Baru', $prestasi->fresh()->judul);
    }

    // ---------- Otorisasi ----------

    public function test_ustadz_bisa_tambah_prestasi_tapi_tidak_kelas_dan_kamar(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($ustadz);

        Livewire::test(ListPrestasiSantris::class)->assertActionVisible('create');

        $this->assertFalse(KelasResource::canViewAny());
        $this->assertFalse(KamarResource::canViewAny());
    }

    // ---------- Route lama ----------

    public function test_halaman_create_dan_edit_lama_sudah_tidak_ada(): void
    {
        $pesantren = Pesantren::factory()->create();
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($this->admin($pesantren));

        $this->get('/admin/santri/kelas/create')->assertNotFound();
        $this->get("/admin/santri/kelas/{$kelas->getRouteKey()}/edit")->assertNotFound();
        $this->get('/admin/santri/kamars/create')->assertNotFound();

        // Prestasi punya route '/{record}' yang ikut menangkap '/create', jadi
        // keberadaan route-nya dicek langsung ke route registry, bukan lewat HTTP.
        foreach (['kelas', 'kamars', 'prestasi'] as $slug) {
            $this->assertFalse(Route::has("filament.admin.santri.resources.{$slug}.create"), "Route create {$slug} masih terdaftar.");
            $this->assertFalse(Route::has("filament.admin.santri.resources.{$slug}.edit"), "Route edit {$slug} masih terdaftar.");
        }
    }
}
