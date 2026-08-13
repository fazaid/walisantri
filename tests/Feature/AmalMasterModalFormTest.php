<?php

namespace Tests\Feature;

use App\Filament\Resources\KesantrianAmalMasters\KesantrianAmalMasterResource;
use App\Filament\Resources\KesantrianAmalMasters\Pages\ListKesantrianAmalMaster;
use App\Models\KesantrianAmalMaster;
use App\Models\Pesantren;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tambah/ubah Master Amal dipindah ke modal, jadi halaman Create/Edit beserta
 * mutateFormDataBeforeCreate()-nya dihapus. Pembuatan kode otomatis kini
 * menumpang ->mutateDataUsing() pada CreateAction.
 */
class AmalMasterModalFormTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(Pesantren $pesantren): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
    }

    /** @return array<string, mixed> */
    private function isian(array $ubah = []): array
    {
        return [
            'label' => 'Setor Hadits Harian',
            'icon' => '📿',
            'tipe' => 'boolean',
            'satuan' => 'hari',
            'bobot' => 7,
            'urutan' => 8,
            'aktif' => true,
            ...$ubah,
        ];
    }

    public function test_tambah_amal_lewat_modal_mengisi_kode_otomatis(): void
    {
        $pesantren = Pesantren::factory()->create();

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianAmalMaster::class)
            ->callAction(CreateAction::class, data: $this->isian())
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('kesantrian_amal_master', [
            'pesantren_id' => $pesantren->id,
            'label' => 'Setor Hadits Harian',
            'kode' => 'setor_hadits_harian',
        ]);
    }

    public function test_kode_bentrok_diberi_akhiran_angka(): void
    {
        $pesantren = Pesantren::factory()->create();

        KesantrianAmalMaster::create($this->isian([
            'pesantren_id' => $pesantren->id,
            'kode' => 'setor_hadits_harian',
        ]));

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianAmalMaster::class)
            ->callAction(CreateAction::class, data: $this->isian(['urutan' => 9]))
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('kesantrian_amal_master', [
            'pesantren_id' => $pesantren->id,
            'kode' => 'setor_hadits_harian_2',
        ]);
    }

    public function test_ubah_amal_lewat_modal_di_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();

        $amal = KesantrianAmalMaster::create($this->isian([
            'pesantren_id' => $pesantren->id,
            'kode' => 'setor_hadits_harian',
        ]));

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianAmalMaster::class)
            ->callAction(TestAction::make('edit')->table($amal), data: $this->isian([
                'label' => 'Setor Hadits Pekanan',
                'tipe' => 'hitungan',
                'nilai_maks' => 3,
            ]))
            ->assertHasNoActionErrors();

        $amal->refresh();
        $this->assertSame('Setor Hadits Pekanan', $amal->label);
        $this->assertSame('hitungan', $amal->tipe);
        // Kode adalah kunci data amalan, jadi tidak boleh ikut berubah.
        $this->assertSame('setor_hadits_harian', $amal->kode);
    }

    public function test_ustadz_tidak_bisa_membuka_daftar_amal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($ustadz)
            ->get(KesantrianAmalMasterResource::getUrl('index'))
            ->assertForbidden();
    }
}
