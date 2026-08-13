<?php

namespace Tests\Feature;

use App\Filament\Pages\MutabaahHarianPage;
use App\Filament\Resources\KesantrianMutabaahs\Pages\ListKesantrianMutabaahs;
use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MutabaahHarianPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeAmalMaster(Pesantren $pesantren, string $kode = 'is_dhuha'): KesantrianAmalMaster
    {
        return KesantrianAmalMaster::create([
            'pesantren_id' => $pesantren->id,
            'kode' => $kode,
            'label' => 'Dhuha',
            'tipe' => 'boolean',
            'satuan' => 'hari',
            'bobot' => 7,
            'urutan' => 1,
            'aktif' => true,
        ]);
    }

    public function test_simpan_yang_gagal_di_tengah_tidak_meninggalkan_data_separuh(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $this->makeAmalMaster($pesantren);

        foreach (['Ahmad', 'Bilal'] as $nama) {
            Santri::factory()->create([
                'pesantren_id' => $pesantren->id,
                'pembimbing_ustadz_id' => $ustadz->id,
                'nama_lengkap' => $nama,
            ]);
        }

        $komponen = Livewire::actingAs($ustadz)->test(MutabaahHarianPage::class);

        // Baris kedua menunjuk santri yang tidak ada — insert-nya ditolak
        // foreign key setelah baris pertama sempat tersimpan.
        // Item Repeater dikunci UUID, bukan indeks angka.
        $rows = $komponen->get('rows');
        $kunciKedua = array_keys($rows)[1];
        $rows[$kunciKedua]['santri_id'] = 999999;

        $komponen->set('rows', $rows)
            ->call('save')
            ->assertNotified('Gagal menyimpan mutaba\'ah');

        // Tanpa transaksi, baris pertama akan tertinggal sebagai data separuh.
        $this->assertSame(0, KesantrianMutabaah::count());
    }

    public function test_simpan_normal_menyimpan_semua_baris(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $this->makeAmalMaster($pesantren);

        foreach (['Ahmad', 'Bilal'] as $nama) {
            Santri::factory()->create([
                'pesantren_id' => $pesantren->id,
                'pembimbing_ustadz_id' => $ustadz->id,
                'nama_lengkap' => $nama,
            ]);
        }

        Livewire::actingAs($ustadz)
            ->test(MutabaahHarianPage::class)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, KesantrianMutabaah::count());
        // Amal centang default terisi, jadi sekali simpan langsung tercatat.
        $this->assertTrue(KesantrianMutabaah::first()->amalan['is_dhuha']);
    }

    public function test_toggle_amal_di_form_satuan_default_terisi(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $this->makeAmalMaster($pesantren);

        // Default form satuan harus sama dengan halaman Isi Harian.
        Livewire::actingAs($admin)
            ->test(ListKesantrianMutabaahs::class)
            ->mountAction(CreateAction::class)
            ->assertActionDataSet(['amalan' => ['is_dhuha' => true]]);
    }
}
