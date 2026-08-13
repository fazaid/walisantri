<?php

namespace Tests\Feature;

use App\Filament\Resources\KesantrianMutabaahs\Pages\ListKesantrianMutabaahs;
use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tambah/ubah Mutaba'ah dipindah ke modal, jadi halaman Create/Edit beserta
 * handleRecordCreation() dan beforeSave()-nya dihapus. Logikanya kini menumpang
 * ->using() dan ->before() pada action — test ini memastikan keduanya tetap
 * jalan dari dalam modal.
 */
class MutabaahModalFormTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(Pesantren $pesantren): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
    }

    private function makeSantri(Pesantren $pesantren): Santri
    {
        return Santri::factory()->create(['pesantren_id' => $pesantren->id]);
    }

    private function makeAmalMaster(Pesantren $pesantren): KesantrianAmalMaster
    {
        return KesantrianAmalMaster::create([
            'pesantren_id' => $pesantren->id,
            'kode' => 'sholat_subuh',
            'label' => 'Sholat Subuh',
            'tipe' => 'boolean',
            'urutan' => 1,
            'aktif' => true,
        ]);
    }

    public function test_tambah_mutabaah_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);
        $this->makeAmalMaster($pesantren);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianMutabaahs::class)
            ->callAction(CreateAction::class, data: [
                'santri_id' => $santri->id,
                'tanggal' => '2026-08-13',
                'status_udzur' => 'Tidak',
                'amalan' => ['sholat_subuh' => true],
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('kesantrian_mutabaah', [
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'status_udzur' => 'Tidak',
        ]);
    }

    public function test_tambah_di_tanggal_yang_sudah_ada_memperbarui_bukan_menambah(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);
        $this->makeAmalMaster($pesantren);

        KesantrianMutabaah::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'tanggal' => '2026-08-13',
            'status_udzur' => 'Tidak',
            'amalan' => ['sholat_subuh' => false],
        ]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianMutabaahs::class)
            ->callAction(CreateAction::class, data: [
                'santri_id' => $santri->id,
                'tanggal' => '2026-08-13',
                'status_udzur' => 'Sakit',
                'amalan' => ['sholat_subuh' => true],
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Data diperbarui, bukan ditambah baru');

        // updateOrCreate: barisnya tetap satu, isinya yang tergantikan.
        $this->assertSame(1, KesantrianMutabaah::count());
        $this->assertSame('Sakit', KesantrianMutabaah::sole()->status_udzur);
    }

    public function test_ubah_mutabaah_lewat_modal_di_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);
        $this->makeAmalMaster($pesantren);

        $mutabaah = KesantrianMutabaah::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'tanggal' => '2026-08-13',
            'status_udzur' => 'Tidak',
            'amalan' => ['sholat_subuh' => false],
        ]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianMutabaahs::class)
            ->callAction(TestAction::make('edit')->table($mutabaah), data: [
                'santri_id' => $santri->id,
                'tanggal' => '2026-08-13',
                'status_udzur' => 'Haid',
                'amalan' => ['sholat_subuh' => true],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Haid', $mutabaah->refresh()->status_udzur);
    }

    public function test_ubah_ke_tanggal_milik_baris_lain_ditolak(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);
        $this->makeAmalMaster($pesantren);

        $dasar = [
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'status_udzur' => 'Tidak',
            'amalan' => ['sholat_subuh' => true],
        ];

        KesantrianMutabaah::create([...$dasar, 'tanggal' => '2026-08-12']);
        $kedua = KesantrianMutabaah::create([...$dasar, 'tanggal' => '2026-08-13']);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianMutabaahs::class)
            ->callAction(TestAction::make('edit')->table($kedua), data: [
                'santri_id' => $santri->id,
                'tanggal' => '2026-08-12',
                'status_udzur' => 'Tidak',
                'amalan' => ['sholat_subuh' => true],
            ])
            ->assertActionHalted(TestAction::make('edit')->table($kedua))
            ->assertNotified('Tanggal bentrok');

        $this->assertSame('2026-08-13', $kedua->refresh()->tanggal->toDateString());
    }

    public function test_ubah_tanpa_ganti_tanggal_tidak_ikut_terhalang(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);
        $this->makeAmalMaster($pesantren);

        $mutabaah = KesantrianMutabaah::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'tanggal' => '2026-08-13',
            'status_udzur' => 'Tidak',
            'amalan' => ['sholat_subuh' => true],
        ]);

        // Barisnya sendiri harus dikecualikan dari pengecekan bentrok.
        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianMutabaahs::class)
            ->callAction(TestAction::make('edit')->table($mutabaah), data: [
                'santri_id' => $santri->id,
                'tanggal' => '2026-08-13',
                'status_udzur' => 'Izin_Pulang',
                'amalan' => ['sholat_subuh' => true],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Izin_Pulang', $mutabaah->refresh()->status_udzur);
    }

    public function test_tombol_isi_harian_dan_amal_tampil_di_header_untuk_admin(): void
    {
        $pesantren = Pesantren::factory()->create();

        // "Isi Harian" & "Amal" tidak lagi jadi tab cluster; jalan masuknya
        // hanya lewat dua tombol header ini.
        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianMutabaahs::class)
            ->assertActionVisible('isiHarian')
            ->assertActionVisible('pengaturanAmal');
    }

    public function test_ustadz_hanya_melihat_tombol_isi_harian(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        // Pengaturan amal tetap admin-only, jadi tombolnya ikut tersembunyi.
        Livewire::actingAs($ustadz)
            ->test(ListKesantrianMutabaahs::class)
            ->assertActionVisible('isiHarian')
            ->assertActionHidden('pengaturanAmal');
    }

    public function test_ustadz_tidak_bisa_hapus_mutabaah_dari_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'pembimbing_ustadz_id' => $ustadz->id,
        ]);
        $this->makeAmalMaster($pesantren);

        $mutabaah = KesantrianMutabaah::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'tanggal' => '2026-08-13',
            'status_udzur' => 'Tidak',
            'amalan' => ['sholat_subuh' => true],
        ]);

        Livewire::actingAs($ustadz)
            ->test(ListKesantrianMutabaahs::class)
            ->assertActionHidden(TestAction::make('delete')->table($mutabaah))
            ->assertActionVisible(TestAction::make('edit')->table($mutabaah));
    }
}
