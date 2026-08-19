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
 * `tahfidz_progress.ustadz_id` dan `tahfidz_rapor.penguji_id` adalah JEJAK AUDIT,
 * bukan penugasan yang ditetapkan siapa pun (§5.4) — dan sampai v4.55 penjagaannya
 * cuma ->default() milik form, yang sekadar nilai awal dan bebas diganti. Dropdown
 * santri disaring ke bimbingan si ustadz, tapi dropdown pencatat/penguji memuat
 * SELURUH ustadz sepesantren, sehingga seorang ustadz bisa mencatat setoran santri
 * bimbingannya sendiri lalu mengkreditkannya ke orang lain tanpa jejak apa pun.
 *
 * Tes ini mengunci stempelnya di sisi server. Kalau suatu saat ustadz memang boleh
 * mencatat atas nama orang lain, tes inilah yang harus diubah lebih dulu.
 */
class JejakAuditTahfidzTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
    }

    private function admin(): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);
    }

    private function ustadz(): User
    {
        return User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);
    }

    private function santriBimbingan(User $ustadz): Santri
    {
        return Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'pembimbing_ustadz_id' => $ustadz->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function dataSetoran(Santri $santri): array
    {
        return [
            'santri_id' => $santri->id,
            'tanggal' => '2026-08-20',
            'tipe_setoran' => 'Sabaq',
            'halaman_mulai' => 1,
            'halaman_selesai' => 3,
            'nilai_kelancaran' => 'Mumtaz',
        ];
    }

    /** @return array<string, mixed> */
    private function dataUjian(Santri $santri): array
    {
        return [
            'santri_id' => $santri->id,
            'tanggal_ujian' => '2026-08-20',
            'target_juz' => 3,
            'status_kelulusan' => 'Lulus',
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai_hafalan' => 90,
            'nilai_tilawah' => 'A',
            'nilai_makhraj' => 'A',
            'nilai_tajwid' => 'A',
            'rekomendasi_pembimbing' => 'Lanjut.',
        ];
    }

    // ---------- Setoran: pencatat ----------

    public function test_setoran_ustadz_distempel_atas_namanya_tanpa_perlu_memilih(): void
    {
        $ustadz = $this->ustadz();
        $santri = $this->santriBimbingan($ustadz);

        // Perhatikan: ustadz_id TIDAK dikirim sama sekali — fieldnya memang tidak
        // ditampilkan untuknya. Inilah penyederhanaannya: satu dropdown lebih sedikit.
        Livewire::actingAs($ustadz)
            ->test(ListTahfidzProgress::class)
            ->callAction(CreateAction::class, data: $this->dataSetoran($santri))
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('tahfidz_progress', [
            'santri_id' => $santri->id,
            'ustadz_id' => $ustadz->id,
        ]);
    }

    public function test_ustadz_tidak_bisa_mengkreditkan_setoran_ke_ustadz_lain(): void
    {
        $ustadz = $this->ustadz();
        $lain = $this->ustadz();
        $santri = $this->santriBimbingan($ustadz);

        Livewire::actingAs($ustadz)
            ->test(ListTahfidzProgress::class)
            ->callAction(CreateAction::class, data: [
                ...$this->dataSetoran($santri),
                'ustadz_id' => $lain->id,
            ])
            ->assertHasNoActionErrors();

        $setoran = TahfidzProgress::withoutGlobalScopes()->firstWhere('santri_id', $santri->id);

        $this->assertSame($ustadz->id, $setoran->ustadz_id);
        $this->assertNotSame($lain->id, $setoran->ustadz_id);
    }

    public function test_admin_tetap_memilih_sendiri_ustadz_pencatatnya(): void
    {
        // Admin tidak menyimak setoran; ia memasukkan data susulan atas nama ustadz
        // yang benar-benar menyimak. Stempel otomatis justru salah untuknya.
        $ustadz = $this->ustadz();
        $santri = $this->santriBimbingan($ustadz);

        Livewire::actingAs($this->admin())
            ->test(ListTahfidzProgress::class)
            ->callAction(CreateAction::class, data: [
                ...$this->dataSetoran($santri),
                'ustadz_id' => $ustadz->id,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('tahfidz_progress', [
            'santri_id' => $santri->id,
            'ustadz_id' => $ustadz->id,
        ]);
    }

    public function test_menyunting_setoran_tidak_menulis_ulang_pencatatnya(): void
    {
        // Stempel sengaja hanya di CreateAction: baris yang dimasukkan admin atas
        // nama Ustadz B tidak boleh berpindah kredit begitu Ustadz A menyuntingnya.
        $penyunting = $this->ustadz();
        $pencatatAsli = $this->ustadz();
        $santri = $this->santriBimbingan($penyunting);

        $setoran = TahfidzProgress::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santri->id,
            'ustadz_id' => $pencatatAsli->id,
            'tanggal' => '2026-08-10',
            'tipe_setoran' => 'Sabaq',
            'halaman_mulai' => 1,
            'halaman_selesai' => 2,
            'nilai_kelancaran' => 'Jayyid',
        ]);

        Livewire::actingAs($penyunting)
            ->test(ListTahfidzProgress::class)
            ->callAction(TestAction::make('edit')->table($setoran), data: [
                ...$this->dataSetoran($santri),
                'tipe_setoran' => 'Manzil',
            ])
            ->assertHasNoActionErrors();

        $setoran->refresh();

        $this->assertSame('Manzil', $setoran->tipe_setoran);
        $this->assertSame($pencatatAsli->id, $setoran->ustadz_id);
    }

    // ---------- Ujian: penguji ----------

    public function test_ujian_ustadz_distempel_atas_namanya_tanpa_perlu_memilih(): void
    {
        $ustadz = $this->ustadz();
        $santri = $this->santriBimbingan($ustadz);

        Livewire::actingAs($ustadz)
            ->test(ListTahfidzUjian::class)
            ->callAction(CreateAction::class, data: $this->dataUjian($santri))
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('tahfidz_rapor', [
            'santri_id' => $santri->id,
            'penguji_id' => $ustadz->id,
        ]);
    }

    public function test_ustadz_tidak_bisa_mengkreditkan_ujian_ke_penguji_lain(): void
    {
        $ustadz = $this->ustadz();
        $lain = $this->ustadz();
        $santri = $this->santriBimbingan($ustadz);

        Livewire::actingAs($ustadz)
            ->test(ListTahfidzUjian::class)
            ->callAction(CreateAction::class, data: [
                ...$this->dataUjian($santri),
                'penguji_id' => $lain->id,
            ])
            ->assertHasNoActionErrors();

        $ujian = TahfidzUjian::withoutGlobalScopes()->firstWhere('santri_id', $santri->id);

        $this->assertSame($ustadz->id, $ujian->penguji_id);
        $this->assertNotSame($lain->id, $ujian->penguji_id);
    }

    public function test_admin_tetap_memilih_sendiri_pengujinya(): void
    {
        $ustadz = $this->ustadz();
        $santri = $this->santriBimbingan($ustadz);

        Livewire::actingAs($this->admin())
            ->test(ListTahfidzUjian::class)
            ->callAction(CreateAction::class, data: [
                ...$this->dataUjian($santri),
                'penguji_id' => $ustadz->id,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('tahfidz_rapor', [
            'santri_id' => $santri->id,
            'penguji_id' => $ustadz->id,
        ]);
    }
}
