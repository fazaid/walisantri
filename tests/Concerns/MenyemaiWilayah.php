<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Fixture wilayah minimal untuk tes yang menyentuh /register.
 *
 * Tabel `wilayah` sengaja dibiarkan kosong oleh migrasi — isinya dimuat
 * `php artisan wilayah:impor` di deploy, dan `migrate:fresh` di suite tes tidak
 * pernah menjalankannya. Jadi setiap tes yang mem-POST /register WAJIB memakai trait
 * ini; tanpa fixturenya, validasi kode desa selalu gagal.
 *
 * Kodenya nyata (Kepmendagri), bukan karangan — dua cabang provinsi supaya uji
 * "kombinasi tidak konsisten" punya pasangan yang sah untuk disilangkan.
 */
trait MenyemaiWilayah
{
    protected function semaiWilayahContoh(): void
    {
        DB::table('wilayah')->insert([
            ['kode' => '32', 'nama' => 'Jawa Barat', 'parent_kode' => null, 'level' => 1],
            ['kode' => '32.01', 'nama' => 'Kabupaten Bogor', 'parent_kode' => '32', 'level' => 2],
            ['kode' => '32.01.01', 'nama' => 'Cibinong', 'parent_kode' => '32.01', 'level' => 3],
            ['kode' => '32.01.01.1006', 'nama' => 'Cibinong', 'parent_kode' => '32.01.01', 'level' => 4],
            ['kode' => '32.01.01.1011', 'nama' => 'Cirimekar', 'parent_kode' => '32.01.01', 'level' => 4],
            ['kode' => '33', 'nama' => 'Jawa Tengah', 'parent_kode' => null, 'level' => 1],
            ['kode' => '33.01', 'nama' => 'Kabupaten Cilacap', 'parent_kode' => '33', 'level' => 2],
            ['kode' => '33.01.01', 'nama' => 'Kedungreja', 'parent_kode' => '33.01', 'level' => 3],
            ['kode' => '33.01.01.2001', 'nama' => 'Tambakreja', 'parent_kode' => '33.01.01', 'level' => 4],
        ]);

        // Daftar provinsi di-cache sehari (Wilayah::provinsi). Tanpa ini, tes yang
        // berjalan setelah tes lain memungut daftar kosong dari cache proses yang sama.
        Cache::forget('wilayah:provinsi');
    }

    /**
     * Kolom wilayah yang sah untuk payload POST /register.
     *
     * @return array<string, string>
     */
    protected function dataWilayahValid(): array
    {
        return [
            'wilayah_provinsi' => '32',
            'wilayah_kota' => '32.01',
            'wilayah_kecamatan' => '32.01.01',
            'wilayah_desa' => '32.01.01.1006',
        ];
    }
}
