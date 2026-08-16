<?php

namespace Tests\Feature;

use App\Enums\StatusKehadiran;
use App\Filament\Pages\RaporPage;
use App\Models\Kelas;
use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianKarakterRapor;
use App\Models\KesantrianMutabaah;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\Santri;
use App\Models\TahfidzUjian;
use App\Models\User;
use App\Services\Rapor\RaporPresensiData;
use App\Support\Waktu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class RaporPageTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    private User $admin;

    private Kelas $kelas;

    private Santri $santri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        $this->admin = User::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'role' => 'admin_pesantren',
        ]);
        $this->kelas = Kelas::factory()->create(['pesantren_id' => $this->pesantren->id]);
        $this->santri = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $this->kelas->id,
            'nama_lengkap' => 'Ahmad',
            'status_aktif' => true,
        ]);
    }

    public function test_wali_santri_tidak_bisa_mengakses(): void
    {
        $wali = User::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'role' => 'wali_santri',
        ]);

        $this->actingAs($wali);
        $this->assertFalse(RaporPage::canAccess());

        $this->actingAs($this->admin);
        $this->assertTrue(RaporPage::canAccess());
    }

    public function test_ustadz_melihat_santri_bimbingan_dan_kelas_yang_diajar(): void
    {
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);

        $bimbingan = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'nama_lengkap' => 'Bilal',
            'pembimbing_ustadz_id' => $ustadz->id,
            'status_aktif' => true,
        ]);

        // Santri di kelas yang diajar, tapi pembimbingnya orang lain.
        MataPelajaran::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $this->kelas->id,
            'ustadz_id' => $ustadz->id,
        ]);

        $luarCakupan = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'nama_lengkap' => 'Cecep',
            'status_aktif' => true,
        ]);

        $opsi = Livewire::actingAs($ustadz)
            ->test(RaporPage::class)
            ->instance()
            ->getSantriOptions();

        $this->assertArrayHasKey($bimbingan->id, $opsi);
        $this->assertArrayHasKey($this->santri->id, $opsi);
        $this->assertArrayNotHasKey($luarCakupan->id, $opsi);
    }

    public function test_santri_pesantren_lain_tidak_muncul(): void
    {
        $lain = Pesantren::factory()->create();
        $santriLain = Santri::factory()->create([
            'pesantren_id' => $lain->id,
            'nama_lengkap' => 'Santri Tetangga',
            'status_aktif' => true,
        ]);

        $opsi = Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->instance()
            ->getSantriOptions();

        $this->assertArrayHasKey($this->santri->id, $opsi);
        $this->assertArrayNotHasKey($santriLain->id, $opsi);
    }

    public function test_menampilkan_nilai_akademik_sesuai_periode(): void
    {
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $this->kelas->id,
            'ustadz_id' => $this->admin->id,
            'nama_mapel' => 'Nahwu',
        ]);

        NilaiAkademik::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai' => 88,
        ]);

        Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->set('santriId', $this->santri->id)
            ->set('tahunAjaran', '2026/2027')
            ->set('periode', 'Semester_Ganjil')
            ->assertSee('Nahwu')
            ->assertSee('88');
    }

    public function test_modul_yang_tidak_dicentang_tidak_ditampilkan(): void
    {
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $this->kelas->id,
            'ustadz_id' => $this->admin->id,
            'nama_mapel' => 'Nahwu',
        ]);

        NilaiAkademik::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai' => 88,
        ]);

        TahfidzUjian::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal_ujian' => '2026-11-10',
            'target_juz' => 3,
            'status_kelulusan' => 'Lulus',
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai_hafalan' => 90,
            'nilai_tilawah' => 'A',
            'nilai_makhraj' => 'A',
            'nilai_tajwid' => 'B',
            'rekomendasi_pembimbing' => 'Lanjut juz berikutnya',
        ]);

        Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->set('santriId', $this->santri->id)
            ->set('tahunAjaran', '2026/2027')
            ->set('periode', 'Semester_Ganjil')
            ->set('modul', ['tahfidz'])
            ->assertSee('Hasil Ujian Tahfidz')
            ->assertDontSee('Nilai Akademik')
            ->assertDontSee('Nahwu');
    }

    public function test_mutabaah_ikut_periode_semester_bukan_bulan_kalender(): void
    {
        KesantrianAmalMaster::create([
            'pesantren_id' => $this->pesantren->id,
            'kode' => 'sholat_subuh',
            'label' => 'Sholat Subuh Berjamaah',
            'tipe' => 'boolean',
            'urutan' => 1,
            'aktif' => true,
        ]);

        // November masuk Semester Ganjil TA 2026/2027 (Juli–Desember 2026).
        KesantrianMutabaah::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal' => '2026-11-05',
            'amalan' => ['sholat_subuh' => true],
            'status_udzur' => 'Tidak',
        ]);

        $komponen = Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->set('santriId', $this->santri->id)
            ->set('tahunAjaran', '2026/2027')
            ->set('modul', ['mutabaah']);

        $komponen->set('periode', 'Semester_Ganjil')
            ->assertSee('Sholat Subuh Berjamaah');

        $komponen->set('periode', 'Semester_Genap')
            ->assertSee('Tidak ada catatan mutabaah pada periode ini')
            ->assertDontSee('Sholat Subuh Berjamaah');
    }

    public function test_karakter_semester_mengabaikan_baris_bulanan(): void
    {
        $isian = [
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tahun_ajaran' => '2026/2027',
            'tanggal_input' => '2026-12-01',
            'log_kasus_khusus' => 'Catatan semester ganjil',
        ];

        KesantrianKarakterRapor::create($isian + ['periode' => 'Semester_Ganjil', 'bulan' => null]);
        KesantrianKarakterRapor::create([
            'periode' => 'Bulanan',
            'bulan' => '11-2026',
            'log_kasus_khusus' => 'Catatan bulanan',
        ] + $isian);

        Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->set('santriId', $this->santri->id)
            ->set('tahunAjaran', '2026/2027')
            ->set('periode', 'Semester_Ganjil')
            ->set('modul', ['karakter'])
            ->assertSee('Catatan semester ganjil')
            ->assertDontSee('Catatan bulanan');
    }

    public function test_santri_di_luar_cakupan_tidak_bisa_dipaksa_lewat_property(): void
    {
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);

        $komponen = Livewire::actingAs($ustadz)
            ->test(RaporPage::class)
            ->set('santriId', $this->santri->id);

        $this->assertNull($komponen->instance()->getSantri());
        $komponen->assertSee('Pilih santri untuk melihat rapor');
    }

    public function test_unduh_pdf_menghasilkan_berkas_untuk_modul_yang_dicentang(): void
    {
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $this->kelas->id,
            'ustadz_id' => $this->admin->id,
            'nama_mapel' => 'Nahwu',
        ]);

        NilaiAkademik::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai' => 88,
        ]);

        $halaman = Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->set('santriId', $this->santri->id)
            ->set('tahunAjaran', '2026/2027')
            ->set('periode', 'Semester_Ganjil')
            ->set('modul', ['akademik', 'tahfidz'])
            ->callAction('unduhPdf')
            ->assertHasNoActionErrors()
            ->assertNotNotified('Belum ada data rapor untuk pilihan ini')
            ->instance();

        // Berkas sengaja dirender penuh: streamDownload menunda output() sampai
        // respons dikirim, sehingga galat template PDF tidak akan terlihat di
        // test kalau hanya mengandalkan callAction.
        $berkas = Pdf::loadView('filament.pdf.rapor-gabungan', [
            'santri' => $halaman->getSantri(),
            'data' => $halaman->getData(),
            'modulLabels' => RaporPage::MODUL,
            'tahunAjaran' => '2026/2027',
            'periodeLabel' => $halaman->getPeriodeLabel(),
        ])->setPaper('A4', 'portrait')->output();

        $this->assertStringStartsWith('%PDF', $berkas);
    }

    public function test_unduh_pdf_memperingatkan_saat_tidak_ada_data(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->set('santriId', $this->santri->id)
            ->set('tahunAjaran', '2026/2027')
            ->set('periode', 'Semester_Ganjil')
            ->callAction('unduhPdf')
            ->assertNotified('Belum ada data rapor untuk pilihan ini');
    }

    public function test_pdf_gabungan_merangkum_seluruh_modul(): void
    {
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $this->kelas->id,
            'ustadz_id' => $this->admin->id,
            'nama_mapel' => 'Nahwu',
        ]);

        NilaiAkademik::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai' => 88,
        ]);

        TahfidzUjian::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal_ujian' => '2026-11-10',
            'target_juz' => 3,
            'status_kelulusan' => 'Lulus',
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai_hafalan' => 90,
            'nilai_tilawah' => 'A',
            'nilai_makhraj' => 'A',
            'nilai_tajwid' => 'B',
            'rekomendasi_pembimbing' => 'Lanjut juz berikutnya',
        ]);

        KesantrianAmalMaster::create([
            'pesantren_id' => $this->pesantren->id,
            'kode' => 'sholat_subuh',
            'label' => 'Sholat Subuh Berjamaah',
            'tipe' => 'boolean',
            'urutan' => 1,
            'aktif' => true,
        ]);

        KesantrianMutabaah::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal' => '2026-11-05',
            'amalan' => ['sholat_subuh' => true],
            'status_udzur' => 'Tidak',
        ]);

        KesantrianKarakterRapor::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'bulan' => null,
            'tanggal_input' => '2026-12-01',
            'log_kasus_khusus' => 'Catatan semester ganjil',
        ]);

        $halaman = Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->set('santriId', $this->santri->id)
            ->set('tahunAjaran', '2026/2027')
            ->set('periode', 'Semester_Ganjil')
            ->assertSee('Nahwu')
            ->assertSee('Hasil Ujian Tahfidz')
            ->assertSee('Sholat Subuh Berjamaah')
            ->assertSee('Catatan semester ganjil')
            ->instance();

        $data = $halaman->getData();

        foreach (['akademik', 'tahfidz', 'mutabaah', 'karakter'] as $modul) {
            $this->assertTrue($data[$modul]['ada_data'], "Modul {$modul} seharusnya berisi data");
        }

        $berkas = Pdf::loadView('filament.pdf.rapor-gabungan', [
            'santri' => $halaman->getSantri(),
            'data' => $data,
            'modulLabels' => RaporPage::MODUL,
            'tahunAjaran' => '2026/2027',
            'periodeLabel' => $halaman->getPeriodeLabel(),
        ])->setPaper('A4', 'portrait')->output();

        $this->assertStringStartsWith('%PDF', $berkas);
        // Empat modul dipisah page-break, jadi berkasnya wajib lebih dari satu halaman.
        $this->assertGreaterThan(1, substr_count($berkas, '/Type /Page'));
    }

    private function catatPresensi(string $tanggal, StatusKehadiran $status): void
    {
        Presensi::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal' => $tanggal,
            'jam_ke' => Presensi::HARIAN,
            'kelas_id' => $this->kelas->id,
            'status' => $status,
        ]);
    }

    public function test_modul_presensi_menampilkan_rekap_kehadiran(): void
    {
        $this->catatPresensi(Waktu::hariIni(), StatusKehadiran::Hadir);

        Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->set('santriId', $this->santri->id)
            ->set('tahunAjaran', '2026/2027')
            ->set('periode', 'Semester_Ganjil')
            ->set('modul', ['presensi'])
            ->assertSee('Presensi')
            ->assertSee('Hari Efektif')
            // "Tanpa Keterangan" wajib dijelaskan di lembar yang sama: ia bukan
            // ketidakhadiran yang dinyatakan, dan rapor disimpan bertahun-tahun.
            ->assertSee('Tanpa Keterangan');
    }

    /**
     * Pesantren yang belum mulai mengabsen tidak mendapat halaman "0% kehadiran".
     *
     * Mencetak nol persen untuk santri yang memang belum pernah diabsen adalah
     * tuduhan, bukan laporan — jadi modulnya tidak ikut tercetak sama sekali.
     */
    public function test_modul_presensi_tidak_punya_data_saat_belum_pernah_diabsen(): void
    {
        $data = RaporPresensiData::untuk($this->santri->id, '2026/2027', 'Semester_Ganjil');

        $this->assertFalse($data['ada_data']);
        $this->assertSame(0, $data['total_tercatat']);
    }

    /**
     * Rentang yang dicetak adalah rentang yang BENAR-BENAR dihitung.
     *
     * Semester Ganjil berakhir Desember, tapi rapor yang dicetak Agustus tidak
     * boleh mengklaim mencakup sampai akhir tahun — sisa periode yang belum
     * berjalan juga tidak boleh masuk penyebut "hari efektif".
     */
    public function test_rentang_presensi_dipotong_ke_hari_ini(): void
    {
        $this->catatPresensi(Waktu::hariIni(), StatusKehadiran::Hadir);

        $data = RaporPresensiData::untuk($this->santri->id, '2026/2027', 'Semester_Ganjil');

        $this->assertTrue($data['ada_data']);
        $this->assertSame(Waktu::hariIni(), $data['akhir']);
        $this->assertLessThanOrEqual(
            Carbon::parse($data['awal'])->diffInDays(Waktu::hariIni()) + 1,
            $data['hari_efektif'],
        );
    }

    public function test_presensi_jam_pelajaran_tidak_ikut_rekap_rapor(): void
    {
        $this->catatPresensi(Waktu::hariIni(), StatusKehadiran::Hadir);

        Presensi::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'tanggal' => Waktu::hariIni(),
            'jam_ke' => 3,
            'kelas_id' => $this->kelas->id,
            'status' => StatusKehadiran::Alpa,
        ]);

        $data = RaporPresensiData::untuk($this->santri->id, '2026/2027', 'Semester_Ganjil');

        // Satu baris tercatat, bukan dua: penyebut "hari efektif" tidak berlaku
        // untuk jam pelajaran, dan mencampurnya membuat persentase tidak berarti.
        $this->assertSame(1, $data['total_tercatat']);
        $this->assertSame(0, collect($data['status'])->firstWhere('label', 'Alpa')['jumlah']);
    }
}
