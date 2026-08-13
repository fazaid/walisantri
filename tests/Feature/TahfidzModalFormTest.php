<?php

namespace Tests\Feature;

use App\Filament\Resources\TahfidzProgress\Pages\ListTahfidzProgress;
use App\Filament\Resources\TahfidzUjian\Pages\ListTahfidzUjian;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\TahfidzProgress;
use App\Models\TahfidzUjian;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tambah/ubah Setoran & Ujian dipindah dari halaman penuh ke modal, mengikuti
 * cluster Akademik: halaman Create/Edit dihapus dan tombol Hapus turun ke tabel
 * plus header halaman Lihat. Test ini menjaga kedua jalur modal tetap menyimpan
 * dan aturan "hanya admin boleh hapus" tetap berlaku di rumah barunya.
 */
class TahfidzModalFormTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(Pesantren $pesantren): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
    }

    private function makeUstadz(Pesantren $pesantren): User
    {
        return User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
    }

    private function makeSantri(Pesantren $pesantren, User $ustadz): Santri
    {
        return Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'pembimbing_ustadz_id' => $ustadz->id,
        ]);
    }

    public function test_tambah_setoran_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = $this->makeUstadz($pesantren);
        $santri = $this->makeSantri($pesantren, $ustadz);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListTahfidzProgress::class)
            ->callAction(CreateAction::class, data: [
                'santri_id' => $santri->id,
                'ustadz_id' => $ustadz->id,
                'tanggal' => '2026-08-13',
                'tipe_setoran' => 'Sabaq',
                'halaman_mulai' => 1,
                'halaman_selesai' => 3,
                'nilai_kelancaran' => 'Mumtaz',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('tahfidz_progress', [
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'tipe_setoran' => 'Sabaq',
            'halaman_selesai' => 3,
        ]);
    }

    public function test_ubah_setoran_lewat_modal_di_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = $this->makeUstadz($pesantren);
        $santri = $this->makeSantri($pesantren, $ustadz);

        $setoran = TahfidzProgress::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'ustadz_id' => $ustadz->id,
            'tanggal' => '2026-08-10',
            'tipe_setoran' => 'Sabaq',
            'halaman_mulai' => 1,
            'halaman_selesai' => 2,
            'nilai_kelancaran' => 'Jayyid',
        ]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListTahfidzProgress::class)
            ->callAction(TestAction::make('edit')->table($setoran), data: [
                'santri_id' => $santri->id,
                'ustadz_id' => $ustadz->id,
                'tanggal' => '2026-08-10',
                'tipe_setoran' => 'Manzil',
                'halaman_mulai' => 1,
                'halaman_selesai' => 5,
                'nilai_kelancaran' => 'Mumtaz',
            ])
            ->assertHasNoActionErrors();

        $setoran->refresh();
        $this->assertSame('Manzil', $setoran->tipe_setoran);
        $this->assertSame(5, $setoran->halaman_selesai);
    }

    public function test_tambah_ujian_lewat_modal(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = $this->makeUstadz($pesantren);
        $santri = $this->makeSantri($pesantren, $ustadz);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListTahfidzUjian::class)
            ->callAction(CreateAction::class, data: [
                'santri_id' => $santri->id,
                'penguji_id' => $ustadz->id,
                'tanggal_ujian' => '2026-08-13',
                'target_juz' => 3,
                'status_kelulusan' => 'Lulus',
                'tahun_ajaran' => '2026/2027',
                'periode' => 'Semester_Ganjil',
                'nilai_hafalan' => 90,
                'nilai_tilawah' => 'A',
                'nilai_makhraj' => 'B',
                'nilai_tajwid' => 'A',
                'rekomendasi_pembimbing' => 'Lanjut ke juz berikutnya.',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('tahfidz_rapor', [
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'target_juz' => 3,
            'status_kelulusan' => 'Lulus',
        ]);
    }

    public function test_ubah_ujian_lewat_modal_di_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = $this->makeUstadz($pesantren);
        $santri = $this->makeSantri($pesantren, $ustadz);

        $dasar = [
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'penguji_id' => $ustadz->id,
            'tanggal_ujian' => '2026-08-13',
            'target_juz' => 3,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai_hafalan' => 70,
            'nilai_tilawah' => 'B',
            'nilai_makhraj' => 'B',
            'nilai_tajwid' => 'B',
            'rekomendasi_pembimbing' => 'Perbaiki makhraj.',
        ];

        $ujian = TahfidzUjian::create([...$dasar, 'status_kelulusan' => 'Mengulang']);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListTahfidzUjian::class)
            ->callAction(TestAction::make('edit')->table($ujian), data: [
                ...$dasar,
                'status_kelulusan' => 'Lulus',
                'nilai_hafalan' => 85,
            ])
            ->assertHasNoActionErrors();

        $ujian->refresh();
        $this->assertSame('Lulus', $ujian->status_kelulusan);
        $this->assertSame(85, (int) $ujian->nilai_hafalan);
    }

    public function test_ustadz_tidak_bisa_hapus_setoran_dari_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = $this->makeUstadz($pesantren);
        $santri = $this->makeSantri($pesantren, $ustadz);

        $setoran = TahfidzProgress::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'ustadz_id' => $ustadz->id,
            'tanggal' => '2026-08-10',
            'tipe_setoran' => 'Sabaq',
            'halaman_mulai' => 1,
            'halaman_selesai' => 2,
            'nilai_kelancaran' => 'Jayyid',
        ]);

        // Hapus pindah dari halaman Edit (sudah tidak ada) ke baris tabel —
        // batasan canDelete() harus tetap menutup tombol itu untuk ustadz.
        Livewire::actingAs($ustadz)
            ->test(ListTahfidzProgress::class)
            ->assertActionHidden(TestAction::make('delete')->table($setoran))
            ->assertActionVisible(TestAction::make('edit')->table($setoran));
    }

    public function test_admin_bisa_hapus_setoran_dari_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = $this->makeUstadz($pesantren);
        $santri = $this->makeSantri($pesantren, $ustadz);

        $setoran = TahfidzProgress::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'ustadz_id' => $ustadz->id,
            'tanggal' => '2026-08-10',
            'tipe_setoran' => 'Sabaq',
            'halaman_mulai' => 1,
            'halaman_selesai' => 2,
            'nilai_kelancaran' => 'Jayyid',
        ]);

        Livewire::actingAs($this->makeAdmin($pesantren))
            ->test(ListTahfidzProgress::class)
            ->callAction(TestAction::make('delete')->table($setoran))
            ->assertHasNoActionErrors();

        $this->assertModelMissing($setoran);
    }

    public function test_ustadz_tidak_bisa_hapus_ujian_dari_tabel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = $this->makeUstadz($pesantren);
        $santri = $this->makeSantri($pesantren, $ustadz);

        $ujian = TahfidzUjian::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'penguji_id' => $ustadz->id,
            'tanggal_ujian' => '2026-08-13',
            'target_juz' => 3,
            'status_kelulusan' => 'Lulus',
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai_hafalan' => 90,
            'nilai_tilawah' => 'A',
            'nilai_makhraj' => 'A',
            'nilai_tajwid' => 'A',
            'rekomendasi_pembimbing' => 'Pertahankan.',
        ]);

        Livewire::actingAs($ustadz)
            ->test(ListTahfidzUjian::class)
            ->assertActionHidden(TestAction::make('delete')->table($ujian))
            ->assertActionVisible(TestAction::make('edit')->table($ujian));
    }
}
