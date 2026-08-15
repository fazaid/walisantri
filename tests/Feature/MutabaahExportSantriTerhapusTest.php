<?php

namespace Tests\Feature;

use App\Exports\MutabaahBulananExport;
use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Services\MutabaahScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ekspor rekap berangkat dari tabel anak, jadi baris milik santri yang di-soft-delete
 * tetap ikut terambil — tapi relasi belongsTo tunduk pada global scope SoftDeletes,
 * sehingga namanya dulu tercetak "-". Hasilnya baris data lengkap tanpa identitas.
 */
class MutabaahExportSantriTerhapusTest extends TestCase
{
    use RefreshDatabase;

    public function test_nama_santri_terhapus_tetap_tercetak_dengan_penanda(): void
    {
        $pesantren = Pesantren::factory()->create();

        KesantrianAmalMaster::create([
            'pesantren_id' => $pesantren->id,
            'kode' => 'is_dhuha',
            'label' => 'Dhuha',
            'tipe' => 'boolean',
            'satuan' => 'hari',
            'bobot' => 7,
            'urutan' => 1,
            'aktif' => true,
        ]);
        MutabaahScoreCalculator::clearCache();

        $aktif = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'nama_lengkap' => 'Ahmad Aktif',
        ]);
        $terhapus = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'nama_lengkap' => 'Bilal Keluar',
        ]);

        foreach ([$aktif, $terhapus] as $santri) {
            KesantrianMutabaah::create([
                'pesantren_id' => $pesantren->id,
                'santri_id' => $santri->id,
                'tanggal' => '2026-09-10',
                'amalan' => ['is_dhuha' => true],
                'status_udzur' => 'Tidak',
            ]);
        }

        $terhapus->delete();
        $this->assertSoftDeleted($terhapus);

        $export = new MutabaahBulananExport($pesantren->id, 9, 2026);
        $nama = $export->collection()->map(fn ($rec) => $export->map($rec)[0])->all();

        $this->assertContains('Ahmad Aktif', $nama);
        $this->assertContains('Bilal Keluar (dihapus)', $nama);
        // Barisnya tidak dibuang: membuangnya akan diam-diam mengubah total
        // rekap satu bulan yang sudah berjalan.
        $this->assertNotContains('-', $nama);
    }
}
