<?php

namespace Tests\Feature;

use App\Filament\Pages\NilaiMassalPage;
use App\Filament\Resources\NilaiAkademiks\Pages\ListNilaiAkademik;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NilaiMassalPageTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    private User $ustadz;

    private Kelas $kelas;

    private MataPelajaran $mapel;

    /** @var array<int, Santri> */
    private array $santri = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        $this->ustadz = User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);
        $this->kelas = Kelas::factory()->create(['pesantren_id' => $this->pesantren->id]);

        $this->mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $this->kelas->id,
            'ustadz_id' => $this->ustadz->id,
            'nama_mapel' => 'Nahwu',
        ]);

        foreach (['Ahmad', 'Bilal', 'Cecep'] as $nama) {
            $this->santri[] = Santri::factory()->create([
                'pesantren_id' => $this->pesantren->id,
                'kelas_id' => $this->kelas->id,
                'nama_lengkap' => $nama,
                'status_aktif' => true,
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function baris(array $nilaiPerIndex): array
    {
        $rows = [];

        foreach ($this->santri as $i => $santri) {
            $rows[] = [
                'santri_id' => $santri->id,
                'nama' => $santri->nama_lengkap,
                'nilai' => $nilaiPerIndex[$i] ?? null,
                'catatan' => null,
            ];
        }

        return $rows;
    }

    public function test_menyimpan_banyak_nilai_dalam_satu_submit(): void
    {
        Livewire::actingAs($this->ustadz)
            ->test(NilaiMassalPage::class)
            ->set('mata_pelajaran_id', $this->mapel->id)
            ->set('tahun_ajaran', '2026/2027')
            ->set('periode', 'Bulanan')
            ->set('bulan', '9-2026')
            ->set('rows', $this->baris([80, 90, 70]))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, NilaiAkademik::count());
        $this->assertSame(
            90,
            NilaiAkademik::where('santri_id', $this->santri[1]->id)->value('nilai')
        );
    }

    public function test_baris_tanpa_nilai_dilewati(): void
    {
        Livewire::actingAs($this->ustadz)
            ->test(NilaiMassalPage::class)
            ->set('mata_pelajaran_id', $this->mapel->id)
            ->set('tahun_ajaran', '2026/2027')
            ->set('periode', 'Bulanan')
            ->set('bulan', '9-2026')
            ->set('rows', $this->baris([80, null, '']))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, NilaiAkademik::count());
    }

    public function test_submit_kedua_memperbarui_bukan_menduplikasi(): void
    {
        $isi = fn (array $nilai) => Livewire::actingAs($this->ustadz)
            ->test(NilaiMassalPage::class)
            ->set('mata_pelajaran_id', $this->mapel->id)
            ->set('tahun_ajaran', '2026/2027')
            ->set('periode', 'Bulanan')
            ->set('bulan', '9-2026')
            ->set('rows', $this->baris($nilai))
            ->call('save')
            ->assertHasNoErrors();

        $isi([80, 90, 70]);
        $isi([85, 90, 70]);

        $this->assertSame(3, NilaiAkademik::count());
        $this->assertSame(
            85,
            NilaiAkademik::where('santri_id', $this->santri[0]->id)->value('nilai')
        );
    }

    /**
     * Untuk periode semester kolom bulan bernilai NULL, dan unique index tidak
     * menegakkan apa pun saat salah satu kolomnya NULL (perilaku NULLS DISTINCT
     * standar, berlaku di Postgres maupun SQLite). updateOrCreate harus tetap
     * menemukan baris lama lewat `where bulan is null`.
     */
    public function test_periode_semester_dengan_bulan_null_tetap_diperbarui(): void
    {
        $isi = fn (array $nilai) => Livewire::actingAs($this->ustadz)
            ->test(NilaiMassalPage::class)
            ->set('mata_pelajaran_id', $this->mapel->id)
            ->set('tahun_ajaran', '2026/2027')
            ->set('periode', 'Semester_Ganjil')
            ->set('rows', $this->baris($nilai))
            ->call('save')
            ->assertHasNoErrors();

        $isi([88, 77, 66]);
        $isi([95, 77, 66]);

        $this->assertSame(3, NilaiAkademik::count());
        $this->assertNull(NilaiAkademik::first()->bulan);
        $this->assertSame(
            95,
            NilaiAkademik::where('santri_id', $this->santri[0]->id)->value('nilai')
        );
    }

    public function test_halaman_tidak_muncul_sebagai_entri_menu(): void
    {
        // Masuknya lewat tombol di header daftar Nilai, bukan lewat navigasi.
        $this->assertFalse(NilaiMassalPage::shouldRegisterNavigation());
    }

    public function test_tombol_input_massal_ada_di_halaman_daftar_nilai(): void
    {
        Livewire::actingAs($this->ustadz)
            ->test(ListNilaiAkademik::class)
            ->assertActionExists('inputMassal');
    }

    public function test_wali_santri_tidak_bisa_mengakses_halaman(): void
    {
        $wali = User::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'role' => 'wali_santri',
        ]);

        $this->actingAs($wali);

        $this->assertFalse(NilaiMassalPage::canAccess());
    }
}
