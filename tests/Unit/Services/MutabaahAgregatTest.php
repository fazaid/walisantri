<?php

namespace Tests\Unit\Services;

use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Services\MutabaahScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MutabaahScoreCalculator::agregat() menggantikan jalur ->get()-lalu-Collection di
 * halaman statistik wali. Angka yang dihasilkannya dibaca wali santri dan tercetak
 * di rapor, jadi yang diuji di sini bukan "hasilnya masuk akal" melainkan
 * "hasilnya IDENTIK dengan jalur lama" — tes ekuivalensi, bukan tes nilai harapan.
 */
class MutabaahAgregatTest extends TestCase
{
    use RefreshDatabase;

    private function siapkanPesantren(): Pesantren
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

        KesantrianAmalMaster::create([
            'pesantren_id' => $pesantren->id,
            'kode' => 'jamaah_5_waktu',
            'label' => 'Jamaah',
            'tipe' => 'hitungan',
            'nilai_maks' => 5,
            'satuan' => 'waktu',
            'bobot' => 10,
            'urutan' => 2,
            'aktif' => true,
        ]);

        MutabaahScoreCalculator::clearCache();

        return $pesantren;
    }

    private function isiMutabaah(Pesantren $pesantren, Santri $santri, int $jumlahHari): void
    {
        for ($i = 0; $i < $jumlahHari; $i++) {
            KesantrianMutabaah::create([
                'pesantren_id' => $pesantren->id,
                'santri_id' => $santri->id,
                'tanggal' => now()->subDays($i)->toDateString(),
                'amalan' => [
                    // Sengaja bervariasi supaya pembulatan benar-benar diuji,
                    // bukan kebetulan cocok karena semua nilainya sama.
                    'is_dhuha' => $i % 3 !== 0,
                    'jamaah_5_waktu' => $i % 6,
                ],
                'status_udzur' => $i % 7 === 0 ? 'Sakit' : 'Tidak',
            ]);
        }
    }

    public function test_agregat_identik_dengan_jalur_collection_lama(): void
    {
        $pesantren = $this->siapkanPesantren();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        // Lebih dari satu chunk (500) supaya akumulasi lintas-chunk ikut teruji.
        $this->isiMutabaah($pesantren, $santri, 620);

        $semua = KesantrianMutabaah::where('santri_id', $santri->id)->get();

        $lama = [
            'total_hari' => $semua->count(),
            'rata_rata' => MutabaahScoreCalculator::persentaseRataRata($semua),
            'breakdown' => MutabaahScoreCalculator::breakdown($semua, $pesantren->id),
        ];

        $baru = MutabaahScoreCalculator::agregat(
            KesantrianMutabaah::where('santri_id', $santri->id),
            $pesantren->id,
        );

        $this->assertSame($lama['total_hari'], $baru['total_hari']);
        $this->assertSame($lama['rata_rata'], $baru['rata_rata']);
        $this->assertEquals($lama['breakdown'], $baru['breakdown']);
    }

    public function test_agregat_tanpa_data_mengembalikan_nol_seperti_jalur_lama(): void
    {
        $pesantren = $this->siapkanPesantren();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        $kosong = KesantrianMutabaah::where('santri_id', $santri->id)->get();

        $baru = MutabaahScoreCalculator::agregat(
            KesantrianMutabaah::where('santri_id', $santri->id),
            $pesantren->id,
        );

        $this->assertSame(0, $baru['total_hari']);
        $this->assertSame(MutabaahScoreCalculator::persentaseRataRata($kosong), $baru['rata_rata']);
        $this->assertEquals(MutabaahScoreCalculator::breakdown($kosong, $pesantren->id), $baru['breakdown']);
    }
}
