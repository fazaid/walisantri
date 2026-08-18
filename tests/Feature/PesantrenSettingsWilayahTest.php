<?php

namespace Tests\Feature;

use App\Filament\Pages\PesantrenSettingsPage;
use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MenyemaiWilayah;
use Tests\TestCase;

/**
 * Kolom wilayah di Pengaturan Pesantren — permukaan kedua (dan satu-satunya jalur
 * koreksi) untuk `profil['wilayah']` yang diisi saat pendaftaran (§4.1).
 *
 * Tanpa halaman ini, salah pilih saat mendaftar bersifat permanen.
 */
class PesantrenSettingsWilayahTest extends TestCase
{
    use MenyemaiWilayah, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semaiWilayahContoh();
    }

    private function adminDari(Pesantren $pesantren): User
    {
        return User::factory()->create([
            'role' => 'admin_pesantren',
            'pesantren_id' => $pesantren->id,
        ]);
    }

    public function test_wilayah_dari_pendaftaran_terisi_ulang_ke_form(): void
    {
        $pesantren = Pesantren::factory()->create([
            'profil' => [
                'wilayah' => [
                    'provinsi' => ['kode' => '32', 'nama' => 'Jawa Barat'],
                    'kota' => ['kode' => '32.01', 'nama' => 'Kabupaten Bogor'],
                    'kecamatan' => ['kode' => '32.01.01', 'nama' => 'Cibinong'],
                    'desa' => ['kode' => '32.01.01.1006', 'nama' => 'Cibinong'],
                ],
                'email_kontak' => 'info@contoh.test',
            ],
        ]);

        $this->actingAs($this->adminDari($pesantren));

        Livewire::test(PesantrenSettingsPage::class)
            ->assertSet('wilayah_provinsi', '32')
            ->assertSet('wilayah_kota', '32.01')
            ->assertSet('wilayah_kecamatan', '32.01.01')
            ->assertSet('wilayah_desa', '32.01.01.1006')
            ->assertSet('email_kontak', 'info@contoh.test');
    }

    public function test_admin_bisa_mengoreksi_wilayah_dan_nama_diambil_ulang_dari_tabel(): void
    {
        $pesantren = Pesantren::factory()->create(['profil' => ['deskripsi' => 'Jangan hilang']]);
        $this->actingAs($this->adminDari($pesantren));

        Livewire::test(PesantrenSettingsPage::class)
            ->set('nama_pesantren', $pesantren->nama_pesantren)
            ->set('pesantren_slug', $pesantren->slug)
            ->set('wilayah_provinsi', '33')
            ->set('wilayah_kota', '33.01')
            ->set('wilayah_kecamatan', '33.01.01')
            ->set('wilayah_desa', '33.01.01.2001')
            ->call('save')
            ->assertHasNoFormErrors();

        $profil = $pesantren->fresh()->profil;

        // Nama tidak diambil dari kiriman klien, melainkan dari tabel wilayah.
        $this->assertSame('Tambakreja', $profil['wilayah']['desa']['nama']);
        $this->assertSame('Jawa Tengah', $profil['wilayah']['provinsi']['nama']);
        // Key lain di profil tidak boleh ikut hilang (array_merge, bukan timpa).
        $this->assertSame('Jangan hilang', $profil['deskripsi']);
    }

    /**
     * Desa dikosongkan = wilayah lama dibiarkan apa adanya, bukan ditimpa null.
     * Tanpa pagar ini, satu simpan pada seksi lain akan menghapus lokasi diam-diam.
     */
    public function test_desa_kosong_tidak_menghapus_wilayah_yang_sudah_ada(): void
    {
        $wilayahLama = [
            'provinsi' => ['kode' => '32', 'nama' => 'Jawa Barat'],
            'kota' => ['kode' => '32.01', 'nama' => 'Kabupaten Bogor'],
            'kecamatan' => ['kode' => '32.01.01', 'nama' => 'Cibinong'],
            'desa' => ['kode' => '32.01.01.1006', 'nama' => 'Cibinong'],
        ];

        $pesantren = Pesantren::factory()->create(['profil' => ['wilayah' => $wilayahLama]]);
        $this->actingAs($this->adminDari($pesantren));

        Livewire::test(PesantrenSettingsPage::class)
            ->set('nama_pesantren', $pesantren->nama_pesantren)
            ->set('pesantren_slug', $pesantren->slug)
            ->set('wilayah_desa', null)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals($wilayahLama, $pesantren->fresh()->profil['wilayah']);
    }
}
