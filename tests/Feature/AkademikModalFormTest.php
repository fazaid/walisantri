<?php

namespace Tests\Feature;

use App\Filament\Resources\EkskulMasters\Pages\ListEkskulMasters;
use App\Filament\Resources\MataPelajarans\Pages\ListMataPelajaran;
use App\Filament\Resources\NilaiAkademiks\Pages\ListNilaiAkademik;
use App\Filament\Resources\SantriEkskuls\Pages\ListSantriEkskuls;
use App\Models\EkskulMaster;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\SantriEkskul;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tambah/edit di cluster Akademik dipindah dari halaman penuh ke modal, jadi
 * halaman Create/Edit beserta hook beforeCreate()/beforeSave()-nya dihapus.
 * Guard duplikat kini menumpang ->before() pada Create/EditAction — test ini
 * memastikan guard itu tetap jalan dari dalam modal.
 */
class AkademikModalFormTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(Pesantren $pesantren): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
    }

    public function test_tambah_mata_pelajaran_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListMataPelajaran::class)
            ->callAction(CreateAction::class, data: [
                'kelas_id' => $kelas->id,
                'nama_mapel' => 'Nahwu',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('mata_pelajaran', [
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nama_mapel' => 'Nahwu',
        ]);
    }

    public function test_ubah_mata_pelajaran_lewat_modal_di_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nama_mapel' => 'Sharaf',
        ]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListMataPelajaran::class)
            ->callAction(TestAction::make('edit')->table($mapel), data: [
                'kelas_id' => $kelas->id,
                'nama_mapel' => 'Sharaf Lanjutan',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Sharaf Lanjutan', $mapel->refresh()->nama_mapel);
    }

    public function test_tambah_master_ekskul_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListEkskulMasters::class)
            ->callAction(CreateAction::class, data: [
                'nama' => 'Panahan',
                'pengajar' => 'Ustadz Hamzah',
                'aktif' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('ekskul_masters', [
            'pesantren_id' => $pesantren->id,
            'nama' => 'Panahan',
        ]);
    }

    public function test_tambah_nilai_akademik_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListNilaiAkademik::class)
            ->callAction(CreateAction::class, data: [
                'mata_pelajaran_id' => $mapel->id,
                'santri_id' => $santri->id,
                'tahun_ajaran' => '2026/2027',
                'periode' => 'Semester_Ganjil',
                'nilai' => 88,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('nilai_akademik', [
            'santri_id' => $santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'nilai' => 88,
        ]);
    }

    public function test_nilai_duplikat_ditolak_dari_dalam_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        NilaiAkademik::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai' => 88,
        ]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListNilaiAkademik::class)
            ->callAction(CreateAction::class, data: [
                'mata_pelajaran_id' => $mapel->id,
                'santri_id' => $santri->id,
                'tahun_ajaran' => '2026/2027',
                'periode' => 'Semester_Ganjil',
                'nilai' => 70,
            ])
            ->assertActionHalted(CreateAction::class)
            ->assertNotified('Nilai untuk santri, mata pelajaran, dan periode ini sudah ada.');

        // Guard berhenti sebelum insert kedua, jadi nilai lama tidak tergeser.
        $this->assertSame(1, NilaiAkademik::count());
        $this->assertSame(88, (int) NilaiAkademik::sole()->nilai);
    }

    public function test_ubah_nilai_ke_kombinasi_milik_baris_lain_ditolak(): void
    {
        $pesantren = Pesantren::factory()->create();
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        $dasar = [
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
        ];

        NilaiAkademik::create([...$dasar, 'periode' => 'Semester_Ganjil', 'nilai' => 88]);
        $genap = NilaiAkademik::create([...$dasar, 'periode' => 'Semester_Genap', 'nilai' => 75]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListNilaiAkademik::class)
            ->callAction(TestAction::make('edit')->table($genap), data: [
                'mata_pelajaran_id' => $mapel->id,
                'santri_id' => $santri->id,
                'tahun_ajaran' => '2026/2027',
                'periode' => 'Semester_Ganjil',
                'nilai' => 75,
            ])
            ->assertActionHalted(TestAction::make('edit')->table($genap))
            ->assertNotified('Nilai untuk santri, mata pelajaran, dan periode ini sudah ada.');

        $this->assertSame('Semester_Genap', $genap->refresh()->periode);
    }

    public function test_ubah_nilai_tanpa_ganti_periode_tidak_ikut_terhalang(): void
    {
        $pesantren = Pesantren::factory()->create();
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        $nilai = NilaiAkademik::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai' => 88,
        ]);

        // Barisnya sendiri harus dikecualikan dari pengecekan duplikat.
        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListNilaiAkademik::class)
            ->callAction(TestAction::make('edit')->table($nilai), data: [
                'mata_pelajaran_id' => $mapel->id,
                'santri_id' => $santri->id,
                'tahun_ajaran' => '2026/2027',
                'periode' => 'Semester_Ganjil',
                'nilai' => 95,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(95, (int) $nilai->refresh()->nilai);
    }

    public function test_ekskul_santri_duplikat_ditolak_dari_dalam_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);
        $ekskul = EkskulMaster::create([
            'pesantren_id' => $pesantren->id,
            'nama' => 'Panahan',
            'aktif' => true,
        ]);

        SantriEkskul::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'ekskul_id' => $ekskul->id,
            'level' => 'pemula',
            'tanggal_mulai' => now()->subMonth()->toDateString(),
            'aktif' => true,
        ]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListSantriEkskuls::class)
            ->callAction(CreateAction::class, data: [
                'santri_id' => $santri->id,
                'ekskul_id' => $ekskul->id,
                'level' => 'mahir',
                'tanggal_mulai' => now()->toDateString(),
                'aktif' => true,
            ])
            ->assertActionHalted(CreateAction::class);

        // Ditahan aturan unique di form sebelum sempat menyentuh guard —
        // guard di ->before() tetap jadi jaring pengaman untuk jalur lain.
        $this->assertSame(1, SantriEkskul::count());
    }
}
