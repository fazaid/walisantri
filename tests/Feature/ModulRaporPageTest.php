<?php

namespace Tests\Feature;

use App\Enums\Modul;
use App\Filament\Pages\RaporPage;
use App\Models\Kelas;
use App\Models\ModulPengaturan;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Halaman Rapor menggabungkan lima modul lewat centang, jadi ia satu-satunya
 * permukaan tempat modul yang dimatikan bisa tetap ikut tercetak.
 *
 * Dua hal yang khas di sini dan tidak berlaku di permukaan lain:
 *
 * 1. 'mutabaah' dan 'karakter' sama-sama menggantung ke tuas Kesantrian — mematikan
 *    satu modul menghilangkan DUA centang. Kalau pemetaannya kelak diubah, tes
 *    inilah yang mengingatkan.
 * 2. $modul adalah properti Livewire PUBLIK, sepenuhnya dikendalikan klien. Tab yang
 *    dibuka sebelum admin mematikan Kesantrian tetap membawa 'mutabaah' di request
 *    berikutnya, dan tanpa penjagaan di isModulAktif() data itu tetap dirakit dan
 *    ikut masuk PDF.
 */
class ModulRaporPageTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        $this->admin = User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);

        $kelas = Kelas::factory()->create(['pesantren_id' => $this->pesantren->id]);
        Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $kelas->id,
            'status_aktif' => true,
        ]);
    }

    private function matikan(Modul ...$modul): void
    {
        ModulPengaturan::untuk($this->pesantren->id)->update(
            collect($modul)->mapWithKeys(fn (Modul $m) => [$m->kolom() => false])->all()
        );
    }

    public function test_seluruh_lima_modul_tersedia_secara_bawaan(): void
    {
        $opsi = Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->instance()
            ->getModulOptions();

        $this->assertSame(
            ['akademik', 'tahfidz', 'mutabaah', 'karakter', 'presensi'],
            array_keys($opsi)
        );
    }

    /** Satu tuas, dua centang — keduanya resource di Cluster Kesantrian. */
    public function test_mematikan_kesantrian_menghilangkan_mutabaah_dan_karakter_sekaligus(): void
    {
        $this->matikan(Modul::Kesantrian);

        $opsi = Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->instance()
            ->getModulOptions();

        $this->assertArrayNotHasKey('mutabaah', $opsi);
        $this->assertArrayNotHasKey('karakter', $opsi);
        $this->assertArrayHasKey('akademik', $opsi);
        $this->assertArrayHasKey('tahfidz', $opsi);
        $this->assertArrayHasKey('presensi', $opsi);
    }

    public function test_modul_yang_dimatikan_tidak_ikut_tercentang_saat_halaman_dibuka(): void
    {
        $this->matikan(Modul::Tahfidz);

        Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->assertSet('modul', ['akademik', 'mutabaah', 'karakter', 'presensi']);
    }

    /**
     * Request basi. Tanpa penjagaan di isModulAktif(), data mutaba'ah tetap dirakit
     * dan ikut tercetak di PDF meski modulnya sudah dimatikan.
     *
     * ⚠️ Tes ini load-bearing: ia satu-satunya yang menyentuh jalur klien-mengirim-
     * kunci-yang-tidak-ada-di-opsi.
     */
    public function test_kunci_modul_basi_dari_klien_tetap_ditolak(): void
    {
        $this->matikan(Modul::Kesantrian);

        $komponen = Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->set('modul', ['mutabaah', 'karakter'])
            ->instance();

        $this->assertFalse($komponen->isModulAktif('mutabaah'));
        $this->assertFalse($komponen->isModulAktif('karakter'));
        $this->assertSame([], array_keys($komponen->getData()));
    }

    public function test_pilih_semua_tidak_menghidupkan_modul_yang_dimatikan(): void
    {
        $this->matikan(Modul::Keuangan, Modul::Kesantrian);

        Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->call('kosongkanModul')
            ->call('pilihSemuaModul')
            ->assertSet('modul', ['akademik', 'tahfidz', 'presensi']);
    }

    public function test_halaman_menjelaskan_diri_saat_seluruh_modul_rapor_dimatikan(): void
    {
        $this->matikan(Modul::Akademik, Modul::Tahfidz, Modul::Kesantrian, Modul::Presensi);

        Livewire::actingAs($this->admin)
            ->test(RaporPage::class)
            ->assertSee('Semua modul rapor sedang dimatikan')
            // Kalimat lama akan bohong: tidak ada satu pun centang untuk dicentang.
            ->assertDontSee('Centang minimal satu rapor');
    }

    public function test_halaman_rapor_hilang_saat_modul_rapor_dimatikan(): void
    {
        $this->matikan(Modul::Rapor);

        $this->actingAs($this->admin);

        $this->assertFalse(RaporPage::canAccess());
    }

    /** Modul Rapor menyala sendiri tetap menampilkan halaman, walau isinya menyusut. */
    public function test_modul_rapor_menyala_tetap_terjangkau_meski_isinya_menyusut(): void
    {
        $this->matikan(Modul::Akademik, Modul::Tahfidz);

        $this->actingAs($this->admin);

        $this->assertTrue(RaporPage::canAccess());
    }
}
