<?php

namespace Tests\Feature;

use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\Pesantren;
use App\Models\Santri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Observer kesehatan → udzur mutaba'ah. PRD menjanjikannya sejak lama tapi kodenya
 * tidak pernah ada; tes ini mengunci perilakunya sekaligus batas-batasnya, terutama
 * larangan membuat baris mutaba'ah baru (lihat kasus terakhir — alasannya aritmetik).
 */
class KesehatanUdzurObserverTest extends TestCase
{
    use RefreshDatabase;

    private function buatRekam(Pesantren $pesantren, Santri $santri, string $status, string $tanggal): KesantrianKesehatan
    {
        return KesantrianKesehatan::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'jenis_rekam' => 'keluhan',
            'tanggal_periksa' => $tanggal,
            'kategori_keluhan' => 'Demam',
            'tindakan_dan_obat' => 'Paracetamol',
            'status_pemulihan' => $status,
        ]);
    }

    private function buatMutabaah(Pesantren $pesantren, Santri $santri, string $tanggal, string $udzur = 'Tidak'): KesantrianMutabaah
    {
        return KesantrianMutabaah::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'tanggal' => $tanggal,
            'amalan' => [],
            'status_udzur' => $udzur,
        ]);
    }

    public function test_istirahat_total_menyetel_udzur_sakit_di_mutabaah_hari_itu(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);
        $tanggal = now()->toDateString();

        $mutabaah = $this->buatMutabaah($pesantren, $santri, $tanggal);
        $this->buatRekam($pesantren, $santri, 'Istirahat_Total', $tanggal);

        $this->assertSame('Sakit', $mutabaah->fresh()->status_udzur);
    }

    public function test_rujukan_luar_juga_menyetel_udzur_sakit(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);
        $tanggal = now()->toDateString();

        $mutabaah = $this->buatMutabaah($pesantren, $santri, $tanggal);
        $this->buatRekam($pesantren, $santri, 'Rujukan_Luar', $tanggal);

        $this->assertSame('Sakit', $mutabaah->fresh()->status_udzur);
    }

    public function test_rawat_mandiri_tidak_menyetel_udzur(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);
        $tanggal = now()->toDateString();

        $mutabaah = $this->buatMutabaah($pesantren, $santri, $tanggal);
        $this->buatRekam($pesantren, $santri, 'Rawat_Mandiri', $tanggal);

        $this->assertSame('Tidak', $mutabaah->fresh()->status_udzur);
    }

    public function test_udzur_yang_lebih_spesifik_tidak_ditimpa(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);
        $tanggal = now()->toDateString();

        // Ustadz sudah menandai Haid — keterangan yang lebih spesifik daripada
        // "Sakit" dan merupakan penilaian manusia, jadi tidak boleh ditimpa.
        $mutabaah = $this->buatMutabaah($pesantren, $santri, $tanggal, 'Haid');
        $this->buatRekam($pesantren, $santri, 'Istirahat_Total', $tanggal);

        $this->assertSame('Haid', $mutabaah->fresh()->status_udzur);
    }

    public function test_status_pemulihan_yang_berubah_jadi_istirahat_total_ikut_menyetel_udzur(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);
        $tanggal = now()->toDateString();

        $mutabaah = $this->buatMutabaah($pesantren, $santri, $tanggal);
        $rekam = $this->buatRekam($pesantren, $santri, 'Rawat_Mandiri', $tanggal);

        $this->assertSame('Tidak', $mutabaah->fresh()->status_udzur);

        $rekam->update(['status_pemulihan' => 'Istirahat_Total']);

        $this->assertSame('Sakit', $mutabaah->fresh()->status_udzur);
    }

    public function test_tidak_membuat_baris_mutabaah_baru_saat_hari_itu_belum_diisi(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        $this->buatRekam($pesantren, $santri, 'Istirahat_Total', now()->toDateString());

        // Kalau observer memakai updateOrCreate, baris kosong ini akan lahir dan
        // masuk penyebut MutabaahScoreCalculator::persentaseRataRata() sebagai
        // hari bernilai 0% — persentase amalan santri turun justru karena ia
        // sakit, dan angka itu dibaca wali di portal serta tercetak di rapor.
        $this->assertSame(0, KesantrianMutabaah::count());
    }

    public function test_hanya_menyentuh_mutabaah_di_tanggal_periksa(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        $kemarin = $this->buatMutabaah($pesantren, $santri, now()->subDay()->toDateString());
        $hariIni = $this->buatMutabaah($pesantren, $santri, now()->toDateString());

        $this->buatRekam($pesantren, $santri, 'Istirahat_Total', now()->toDateString());

        $this->assertSame('Tidak', $kemarin->fresh()->status_udzur);
        $this->assertSame('Sakit', $hariIni->fresh()->status_udzur);
    }
}
