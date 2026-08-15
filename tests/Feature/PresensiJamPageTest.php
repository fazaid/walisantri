<?php

namespace Tests\Feature;

use App\Enums\StatusKehadiran;
use App\Filament\Pages\PresensiJamPage;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\PresensiPengaturan;
use App\Models\Santri;
use App\Models\User;
use App\Support\PresensiDefault;
use App\Support\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Presensi per jam pelajaran (Fase 6) — mode opsional, mati secara bawaan.
 *
 * Dua hal yang paling mudah salah dan karena itu diuji berulang: baris per jam
 * TIDAK BOLEH menabrak presensi harian santri yang sama (unique-nya
 * `(santri_id, tanggal, jam_ke)`, dan `jam_ke = 0` sudah dipakai harian), dan
 * cakupannya adalah PENGAMPU MAPEL — bukan wali kelas.
 */
class PresensiJamPageTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Pesantren, 1: User, 2: User, 3: Kelas, 4: MataPelajaran} */
    private function siapkan(bool $aktif = true, int $jumlahSantri = 2): array
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'ustadz_id' => $ustadz->id,
            'nama_mapel' => 'Fiqih',
        ]);

        // ⚠️ SantriFactory TIDAK mengisi kelas_id — tanpa baris ini gridnya kosong
        // dan tesnya lulus tanpa menguji apa pun.
        for ($i = 0; $i < $jumlahSantri; $i++) {
            Santri::factory()->create([
                'pesantren_id' => $pesantren->id,
                'kelas_id' => $kelas->id,
                'nama_lengkap' => 'Santri '.$i,
            ]);
        }

        PresensiDefault::untukPesantren($pesantren->id);
        PresensiPengaturan::untuk($pesantren->id)->update(['presensi_per_jam_aktif' => $aktif]);

        return [$pesantren, $admin, $ustadz, $kelas, $mapel];
    }

    public function test_halaman_menolak_bekerja_saat_fitur_belum_diaktifkan(): void
    {
        [, $admin] = $this->siapkan(aktif: false);

        $komponen = Livewire::actingAs($admin)->test(PresensiJamPage::class);
        $peringatan = $komponen->instance()->peringatanKosong();

        $this->assertNotNull($peringatan);
        $this->assertStringContainsString('belum diaktifkan', $peringatan['judul']);
        // Admin adalah orang yang BISA menyalakannya, jadi pesannya menyebut jalannya.
        $this->assertStringContainsString('Pengaturan', $peringatan['saran']);
    }

    /**
     * Layar dijaga peringatanKosong(); DATA dijaga save().
     *
     * Request Livewire yang dirakit tangan tidak pernah melewati view sama sekali,
     * jadi penjagaan di layar saja tidak menjaga apa pun.
     */
    public function test_save_menolak_menulis_saat_fitur_belum_diaktifkan(): void
    {
        [, $admin] = $this->siapkan(aktif: false);

        Livewire::actingAs($admin)
            ->test(PresensiJamPage::class)
            ->call('save')
            ->assertNotified('Presensi per jam pelajaran tidak aktif');

        $this->assertSame(0, Presensi::count());
    }

    public function test_admin_menyimpan_presensi_satu_jam_pelajaran(): void
    {
        [, $admin, , $kelas, $mapel] = $this->siapkan(jumlahSantri: 3);

        Livewire::actingAs($admin)
            ->test(PresensiJamPage::class)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, Presensi::count());

        $baris = Presensi::first();
        $this->assertSame(1, $baris->jam_ke);
        $this->assertSame($mapel->id, $baris->mata_pelajaran_id);
        // kelas_id diturunkan dari mapel, bukan dari kiriman klien.
        $this->assertSame($kelas->id, $baris->kelas_id);
        $this->assertSame(StatusKehadiran::Hadir, $baris->status);
        $this->assertSame($admin->id, $baris->dicatat_oleh);
    }

    /**
     * Inti keputusan `jam_ke` NOT NULL DEFAULT 0.
     *
     * Satu santri pada satu tanggal punya SATU presensi harian (jam_ke = 0) dan
     * boleh punya presensi tiap jam pelajaran (jam_ke = 1..N) sekaligus — keduanya
     * hidup berdampingan tanpa saling menimpa.
     */
    public function test_presensi_per_jam_tidak_menabrak_presensi_harian(): void
    {
        [$pesantren, $admin, , $kelas] = $this->siapkan(jumlahSantri: 1);
        $santri = Santri::first();

        Presensi::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'tanggal' => Waktu::hariIni(),
            'jam_ke' => Presensi::HARIAN,
            'kelas_id' => $kelas->id,
            'status' => StatusKehadiran::Alpa,
        ]);

        Livewire::actingAs($admin)
            ->test(PresensiJamPage::class)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Presensi::where('santri_id', $santri->id)->count());
        // Baris hariannya tidak tersentuh.
        $this->assertSame(StatusKehadiran::Alpa, Presensi::where('santri_id', $santri->id)
            ->where('jam_ke', Presensi::HARIAN)->first()->status);
        $this->assertSame(StatusKehadiran::Hadir, Presensi::where('santri_id', $santri->id)
            ->where('jam_ke', 1)->first()->status);
    }

    public function test_simpan_ulang_memperbarui_bukan_menduplikasi(): void
    {
        [, $admin] = $this->siapkan(jumlahSantri: 2);

        $komponen = Livewire::actingAs($admin)->test(PresensiJamPage::class);
        $komponen->call('save')->assertHasNoErrors();

        $rows = $komponen->get('rows');
        $kunci = array_key_first($rows);
        $rows[$kunci]['status'] = StatusKehadiran::Sakit->value;

        $komponen->set('rows', $rows)->call('save')->assertHasNoErrors();

        $this->assertSame(2, Presensi::count());
        $this->assertSame(1, Presensi::where('status', StatusKehadiran::Sakit->value)->count());
    }

    public function test_ustadz_pengampu_bisa_mengisi_mapel_yang_ia_ampu(): void
    {
        [, , $ustadz, , $mapel] = $this->siapkan(jumlahSantri: 2);

        Livewire::actingAs($ustadz)
            ->test(PresensiJamPage::class)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Presensi::count());
        $this->assertSame($mapel->id, Presensi::first()->mata_pelajaran_id);
    }

    /**
     * §5.4 — penugasan di satu jalur tidak membuka jalur lain.
     *
     * Wali kelas memegang presensi HARIAN kelasnya. Ia tidak otomatis memegang
     * presensi jam pelajaran di kelas yang sama; yang berdiri di depan kelas pada
     * jam itu adalah pengampunya.
     */
    public function test_wali_kelas_yang_bukan_pengampu_tidak_bisa_mengisi(): void
    {
        [$pesantren, , , $kelas] = $this->siapkan(jumlahSantri: 2);

        $wali = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $kelas->update(['wali_kelas_id' => $wali->id]);

        $komponen = Livewire::actingAs($wali)->test(PresensiJamPage::class);
        $peringatan = $komponen->instance()->peringatanKosong();

        $this->assertNotNull($peringatan);
        $this->assertStringContainsString('pengampu', $peringatan['judul']);

        $komponen->call('save');
        $this->assertSame(0, Presensi::count());
    }

    public function test_ustadz_tidak_bisa_menyimpan_untuk_mapel_yang_bukan_miliknya(): void
    {
        [$pesantren, , $ustadz] = $this->siapkan(jumlahSantri: 2);

        $kelasLain = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $mapelOrangLain = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelasLain->id,
            'ustadz_id' => User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id])->id,
            'nama_mapel' => 'Tafsir',
        ]);

        $komponen = Livewire::actingAs($ustadz)->test(PresensiJamPage::class);

        // Lapis pertama: mapel orang lain tidak pernah muncul sebagai pilihan.
        $this->assertArrayNotHasKey($mapelOrangLain->id, $komponen->instance()->mapelOptions());

        // Lapis kedua — yang sebenarnya dijaga di sini. Lewat UI, validasi Select
        // menolaknya lebih dulu; yang tersisa adalah request yang dirakit tangan,
        // dan itu tidak pernah melewati validasi form sama sekali.
        $this->assertNull($komponen->instance()->mapelTerpilih($mapelOrangLain->id));

        $komponen->set('mata_pelajaran_id', (string) $mapelOrangLain->id)->call('save');

        $this->assertSame(0, Presensi::where('mata_pelajaran_id', $mapelOrangLain->id)->count());
    }

    /**
     * santri_id dari klien tidak dipercaya.
     *
     * Repeater mengirim balik apa pun yang ada di state-nya, dan request yang
     * dirakit tangan bisa menyelipkan santri kelas lain. Yang menentukan siapa
     * yang boleh ditulis adalah kelas milik mapel.
     */
    public function test_santri_di_luar_kelas_mapel_disaring_saat_menyimpan(): void
    {
        [$pesantren, $admin] = $this->siapkan(jumlahSantri: 2);

        $kelasLain = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $penyusup = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelasLain->id,
            'nama_lengkap' => 'Santri Kelas Lain',
        ]);

        $komponen = Livewire::actingAs($admin)->test(PresensiJamPage::class);
        $rows = $komponen->get('rows');
        $rows[] = [
            'santri_id' => $penyusup->id,
            'nama' => $penyusup->nama_lengkap,
            'status' => StatusKehadiran::Hadir->value,
            'catatan' => null,
        ];

        $komponen->set('rows', $rows)->call('save')->assertHasNoErrors();

        $this->assertSame(2, Presensi::count());
        $this->assertSame(0, Presensi::where('santri_id', $penyusup->id)->count());
    }

    /**
     * jam_ke = 0 adalah presensi HARIAN.
     *
     * Kalau halaman ini boleh menulis dengan jam_ke = 0, ia akan MENIMPA presensi
     * harian santri lewat unique (santri_id, tanggal, jam_ke) — diam-diam, dan
     * dengan mata_pelajaran_id yang seharusnya tidak ada di baris harian.
     */
    public function test_tidak_bisa_menimpa_presensi_harian_lewat_jam_ke_nol(): void
    {
        [$pesantren, $admin, , $kelas] = $this->siapkan(jumlahSantri: 1);
        $santri = Santri::first();

        $harian = Presensi::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'tanggal' => Waktu::hariIni(),
            'jam_ke' => Presensi::HARIAN,
            'kelas_id' => $kelas->id,
            'status' => StatusKehadiran::Alpa,
        ]);

        Livewire::actingAs($admin)
            ->test(PresensiJamPage::class)
            ->set('jam_ke', '0')
            ->call('save');

        $harian->refresh();
        $this->assertSame(StatusKehadiran::Alpa, $harian->status);
        $this->assertNull($harian->mata_pelajaran_id);
        $this->assertSame(1, Presensi::count());
    }

    public function test_ustadz_ditolak_di_luar_jendela_edit(): void
    {
        [$pesantren, , $ustadz] = $this->siapkan(jumlahSantri: 2);

        PresensiPengaturan::untuk($pesantren->id)->update(['batas_edit_ustadz_hari' => 3]);

        Livewire::actingAs($ustadz)
            ->test(PresensiJamPage::class)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Presensi::count());

        // Lapis kedua diuji dengan memanggil pengecekannya langsung: lewat UI,
        // ->minDate() menangkapnya lebih dulu sehingga jalur ini tidak pernah
        // tersentuh — dan justru itu alasannya ada.
        $komponen = Livewire::actingAs($ustadz)->test(PresensiJamPage::class);
        $lampau = Waktu::sekarang()->subDays(30)->toDateString();

        $this->assertFalse($komponen->instance()->tanggalDalamJendelaEdit($lampau));
    }

    public function test_admin_tidak_terkena_jendela_edit(): void
    {
        [$pesantren, $admin] = $this->siapkan(jumlahSantri: 1);

        PresensiPengaturan::untuk($pesantren->id)->update(['batas_edit_ustadz_hari' => 3]);

        $komponen = Livewire::actingAs($admin)->test(PresensiJamPage::class);
        $lampau = Waktu::sekarang()->subDays(90)->toDateString();

        $this->assertTrue($komponen->instance()->tanggalDalamJendelaEdit($lampau));
    }

    public function test_tombol_isi_per_jam_mengikuti_toggle_pengaturan(): void
    {
        [$pesantren, $admin] = $this->siapkan(aktif: false, jumlahSantri: 1);

        $this->actingAs($admin);
        $this->assertFalse(PresensiJamPage::aktifUntukPengguna());

        PresensiPengaturan::untuk($pesantren->id)->update(['presensi_per_jam_aktif' => true]);
        $this->assertTrue(PresensiJamPage::aktifUntukPengguna());
    }

    public function test_super_admin_tidak_bisa_mengakses(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin);
        $this->assertFalse(PresensiJamPage::canAccess());
    }
}
