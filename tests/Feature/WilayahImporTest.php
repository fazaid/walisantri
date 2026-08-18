<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `wilayah:impor` — pemuat data referensi yang dipanggil deploy.yml setelah
 * `migrate --force`. Yang dijaga di sini: idempotensinya (deploy jalan tiap rilis)
 * dan penurunan parent_kode/level (kolom yang tidak ada di berkas sumber).
 */
class WilayahImporTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE = 'tests/Fixtures/wilayah-mini.csv';

    public function test_impor_memuat_berkas_dan_menurunkan_parent_serta_level(): void
    {
        $this->artisan('wilayah:impor', ['--berkas' => self::FIXTURE])->assertSuccessful();

        $this->assertDatabaseCount('wilayah', 4);

        $this->assertDatabaseHas('wilayah', [
            'kode' => '32', 'nama' => 'Jawa Barat', 'parent_kode' => null, 'level' => 1,
        ]);
        $this->assertDatabaseHas('wilayah', [
            'kode' => '32.01', 'nama' => 'Kabupaten Bogor', 'parent_kode' => '32', 'level' => 2,
        ]);
        $this->assertDatabaseHas('wilayah', [
            'kode' => '32.01.01.1006', 'nama' => 'Cibinong', 'parent_kode' => '32.01.01', 'level' => 4,
        ]);
    }

    public function test_impor_kedua_kali_dilewati_tanpa_menduplikasi(): void
    {
        $this->artisan('wilayah:impor', ['--berkas' => self::FIXTURE])->assertSuccessful();

        $this->artisan('wilayah:impor', ['--berkas' => self::FIXTURE])
            ->expectsOutputToContain('sudah sinkron')
            ->assertSuccessful();

        $this->assertDatabaseCount('wilayah', 4);
    }

    /**
     * Jumlah baris yang menyimpang dari metadata berkas = tabel rusak/ketinggalan,
     * dan itu harus memicu muat ulang tanpa perlu --paksa.
     */
    public function test_data_yang_tidak_lengkap_dimuat_ulang_tanpa_paksa(): void
    {
        $this->artisan('wilayah:impor', ['--berkas' => self::FIXTURE])->assertSuccessful();

        DB::table('wilayah')->where('kode', '32.01')->delete();
        $this->assertDatabaseCount('wilayah', 3);

        $this->artisan('wilayah:impor', ['--berkas' => self::FIXTURE])
            ->doesntExpectOutputToContain('sudah sinkron')
            ->assertSuccessful();

        $this->assertDatabaseCount('wilayah', 4);
    }

    public function test_paksa_memuat_ulang_walau_jumlah_baris_sudah_cocok(): void
    {
        $this->artisan('wilayah:impor', ['--berkas' => self::FIXTURE])->assertSuccessful();

        $this->artisan('wilayah:impor', ['--berkas' => self::FIXTURE, '--paksa' => true])
            ->doesntExpectOutputToContain('sudah sinkron')
            ->assertSuccessful();

        $this->assertDatabaseCount('wilayah', 4);
    }

    public function test_berkas_tidak_ada_menggagalkan_perintah(): void
    {
        $this->artisan('wilayah:impor', ['--berkas' => 'tests/Fixtures/tidak-ada.csv'])->assertFailed();

        $this->assertDatabaseCount('wilayah', 0);
    }
}
