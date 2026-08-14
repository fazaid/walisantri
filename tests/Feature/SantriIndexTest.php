<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Mengunci aturan wajib §1.7 poin 3 untuk tabel `santri`.
 *
 * Kedua index ini sempat hilang setahun tanpa ketahuan: `2026_06_05_000003`
 * men-drop versi lamanya saat kelas & kamar berubah jadi FK dan tidak pernah
 * membangunnya kembali. Tes ini bukan untuk mengukur performa, melainkan supaya
 * penghapusan berikutnya harus jadi keputusan sadar, bukan efek samping migrasi
 * yang mengubah tipe kolom.
 */
class SantriIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_kelas_dan_kamar_ada_di_tabel_santri(): void
    {
        $nama = collect(Schema::getIndexes('santri'))->pluck('name');

        $this->assertContains('santri_tenant_kelas_idx', $nama);
        $this->assertContains('santri_tenant_kamar_idx', $nama);
    }

    public function test_index_diawali_kolom_pesantren_id(): void
    {
        $index = collect(Schema::getIndexes('santri'))
            ->whereIn('name', ['santri_tenant_kelas_idx', 'santri_tenant_kamar_idx']);

        $this->assertCount(2, $index);

        $index->each(function (array $i) {
            $this->assertSame('pesantren_id', $i['columns'][0]);
        });
    }
}
