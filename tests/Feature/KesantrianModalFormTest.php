<?php

namespace Tests\Feature;

use App\Filament\Resources\KesantrianInventaris\Pages\ListKesantrianInventaris;
use App\Filament\Resources\KesantrianKarakterRapors\Pages\ListKesantrianKarakterRapors;
use App\Filament\Resources\KesantrianKesehatans\Pages\ListKesantrianKesehatans;
use App\Models\KesantrianInventaris;
use App\Models\KesantrianKarakterRapor;
use App\Models\KesantrianKesehatan;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Karakter, Rekam Medis, dan Inventaris dipindah dari halaman penuh ke modal,
 * jadi halaman Create/Edit-nya dihapus. Guard duplikat rapor karakter yang dulu
 * menempel di beforeCreate()/beforeSave() kini menumpang ->before() pada action.
 */
class KesantrianModalFormTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(Pesantren $pesantren): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
    }

    private function makeSantri(Pesantren $pesantren, ?User $ustadz = null): Santri
    {
        return Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            ...($ustadz ? ['pembimbing_ustadz_id' => $ustadz->id] : []),
        ]);
    }

    /**
     * Periode 'Bulanan' dipakai di semua kasus karakter: migrasi yang
     * melonggarkan CHECK periode sengaja melewati SQLite, jadi di test
     * hanya 'Bulanan'/'Semester' yang lolos. Sekalian menguji cabang bulan
     * pada guard duplikat.
     *
     * @return array<string, mixed>
     */
    private function nilaiKarakter(): array
    {
        $fields = [
            'adab_ustadz', 'adab_tamu', 'adab_asrama', 'adab_kelas', 'adab_sholat',
            'adab_quran', 'adab_minum', 'kepribadian_tanggungjawab', 'kepribadian_kemandirian',
            'kepribadian_kepatuhan', 'kepribadian_kebersihan', 'kepribadian_mengelola',
            'kepribadian_kepedulian', 'kepribadian_empati', 'kepribadian_kebersamaan',
            'kepribadian_kedisiplinan',
        ];

        return array_fill_keys($fields, 'B');
    }

    // ---------- Karakter ----------

    public function test_tambah_karakter_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianKarakterRapors::class)
            ->callAction(CreateAction::class, data: [
                'santri_id' => $santri->id,
                'tahun_ajaran' => '2026/2027',
                'periode' => 'Bulanan',
                'bulan' => '8-2026',
                'tanggal_input' => '2026-08-13',
                ...$this->nilaiKarakter(),
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('kesantrian_karakter_rapor', [
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'periode' => 'Bulanan',
            'bulan' => '8-2026',
        ]);
    }

    public function test_karakter_duplikat_ditolak_dari_dalam_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);

        KesantrianKarakterRapor::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Bulanan',
            'bulan' => '8-2026',
            'tanggal_input' => '2026-08-13',
            ...$this->nilaiKarakter(),
        ]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianKarakterRapors::class)
            ->callAction(CreateAction::class, data: [
                'santri_id' => $santri->id,
                'tahun_ajaran' => '2026/2027',
                'periode' => 'Bulanan',
                'bulan' => '8-2026',
                'tanggal_input' => '2026-08-13',
                ...$this->nilaiKarakter(),
            ])
            ->assertActionHalted(CreateAction::class)
            ->assertNotified('Data sudah ada');

        $this->assertSame(1, KesantrianKarakterRapor::count());
    }

    public function test_ubah_karakter_tanpa_ganti_periode_tidak_ikut_terhalang(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);

        $rapor = KesantrianKarakterRapor::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Bulanan',
            'bulan' => '8-2026',
            'tanggal_input' => '2026-08-13',
            ...$this->nilaiKarakter(),
        ]);

        // Barisnya sendiri harus dikecualikan dari pengecekan duplikat.
        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianKarakterRapors::class)
            ->callAction(TestAction::make('edit')->table($rapor), data: [
                'santri_id' => $santri->id,
                'tahun_ajaran' => '2026/2027',
                'periode' => 'Bulanan',
                'bulan' => '8-2026',
                'tanggal_input' => '2026-08-13',
                ...$this->nilaiKarakter(),
                'adab_ustadz' => 'A',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('A', $rapor->refresh()->adab_ustadz);
    }

    // ---------- Rekam Medis ----------

    public function test_tambah_rekam_medis_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianKesehatans::class)
            ->callAction(CreateAction::class, data: [
                'santri_id' => $santri->id,
                'tanggal_periksa' => '2026-08-13',
                'jenis_rekam' => 'keluhan',
                'kategori_keluhan' => 'Demam',
                'tindakan_dan_obat' => 'Parasetamol',
                'status_pemulihan' => 'Rawat_Mandiri',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('kesantrian_kesehatan', [
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'kategori_keluhan' => 'Demam',
        ]);
    }

    public function test_ubah_rekam_medis_lewat_modal_di_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);

        $rekam = KesantrianKesehatan::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'tanggal_periksa' => '2026-08-13',
            'jenis_rekam' => 'keluhan',
            'kategori_keluhan' => 'Demam',
            'tindakan_dan_obat' => 'Parasetamol',
            'status_pemulihan' => 'Rawat_Mandiri',
        ]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianKesehatans::class)
            ->callAction(TestAction::make('edit')->table($rekam), data: [
                'santri_id' => $santri->id,
                'tanggal_periksa' => '2026-08-13',
                'jenis_rekam' => 'keluhan',
                'kategori_keluhan' => 'Pusing',
                'tindakan_dan_obat' => 'Istirahat',
                'status_pemulihan' => 'Istirahat_Total',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Pusing', $rekam->refresh()->kategori_keluhan);
    }

    // ---------- Inventaris ----------

    public function test_tambah_inventaris_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianInventaris::class)
            ->callAction(CreateAction::class, data: [
                'santri_id' => $santri->id,
                'nama_barang_umum' => 'Sarung',
                'kode_unik_fisik' => 'FZ-SRG-01',
                'kuota_regulasi_maksimal' => 2,
                'kondisi_barang' => 'Baik',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('kesantrian_inventaris', [
            'pesantren_id' => $pesantren->id,
            'kode_unik_fisik' => 'FZ-SRG-01',
        ]);
    }

    public function test_ubah_inventaris_lewat_modal_di_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = $this->makeSantri($pesantren);

        $barang = KesantrianInventaris::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'nama_barang_umum' => 'Sarung',
            'kode_unik_fisik' => 'FZ-SRG-01',
            'kuota_regulasi_maksimal' => 2,
            'kondisi_barang' => 'Baik',
        ]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListKesantrianInventaris::class)
            ->callAction(TestAction::make('edit')->table($barang), data: [
                'santri_id' => $santri->id,
                'nama_barang_umum' => 'Sarung',
                'kode_unik_fisik' => 'FZ-SRG-01',
                'kuota_regulasi_maksimal' => 2,
                'kondisi_barang' => 'Hilang',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Hilang', $barang->refresh()->kondisi_barang);
    }

    // ---------- Hapus khusus admin ----------

    public function test_ustadz_tidak_bisa_hapus_dari_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $santri = $this->makeSantri($pesantren, $ustadz);

        $barang = KesantrianInventaris::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'nama_barang_umum' => 'Sarung',
            'kode_unik_fisik' => 'FZ-SRG-01',
            'kuota_regulasi_maksimal' => 2,
            'kondisi_barang' => 'Baik',
        ]);

        Livewire::actingAs($ustadz)
            ->test(ListKesantrianInventaris::class)
            ->assertActionHidden(TestAction::make('delete')->table($barang))
            ->assertActionVisible(TestAction::make('edit')->table($barang));
    }
}
