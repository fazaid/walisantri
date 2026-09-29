<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Services\SantriDetailPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kartu "Nilai Akademik" di dashboard wali defaultnya membaca semester berjalan
 * (§commit 64a6081), tapi periode tidak dikonfigurasi per tenant — sebagian
 * pesantren input nilai per bulan (periode='Bulanan'). SantriDetailPresenter
 * harus fallback ke bulan berjalan saat semester kosong, supaya pesantren yang
 * pakai mode bulanan tidak selalu melihat "Belum ada data".
 */
class SantriDetailNilaiAkademikTest extends TestCase
{
    use RefreshDatabase;

    private function buatSantri(): Santri
    {
        $pesantren = Pesantren::factory()->create();
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        return Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'status_aktif' => true,
        ]);
    }

    public function test_fallback_ke_nilai_bulanan_saat_semester_berjalan_kosong(): void
    {
        $this->travelTo('2026-11-15');

        $santri = $this->buatSantri();
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $santri->pesantren_id,
            'kelas_id' => $santri->kelas_id,
        ]);

        NilaiAkademik::create([
            'pesantren_id' => $santri->pesantren_id,
            'santri_id' => $santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Bulanan',
            'bulan' => '11-2026',
            'nilai' => 90,
        ]);

        $nilaiAkademik = SantriDetailPresenter::detail($santri)['nilaiAkademik'];

        $this->assertTrue($nilaiAkademik['ada_data']);
        $this->assertSame(90.0, $nilaiAkademik['rata_rata']);
        $this->assertSame(1, $nilaiAkademik['jumlah_mapel']);
        $this->assertSame('bulan ini', $nilaiAkademik['label_periode']);
    }

    public function test_semester_berjalan_tetap_diprioritaskan_meski_ada_data_bulanan(): void
    {
        $this->travelTo('2026-11-15');

        $santri = $this->buatSantri();
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $santri->pesantren_id,
            'kelas_id' => $santri->kelas_id,
        ]);

        NilaiAkademik::create([
            'pesantren_id' => $santri->pesantren_id,
            'santri_id' => $santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai' => 80,
        ]);

        NilaiAkademik::create([
            'pesantren_id' => $santri->pesantren_id,
            'santri_id' => $santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Bulanan',
            'bulan' => '11-2026',
            'nilai' => 60,
        ]);

        $nilaiAkademik = SantriDetailPresenter::detail($santri)['nilaiAkademik'];

        $this->assertSame(80.0, $nilaiAkademik['rata_rata']);
        $this->assertSame('semester ini', $nilaiAkademik['label_periode']);
    }

    public function test_belum_ada_data_saat_semester_dan_bulan_berjalan_kosong(): void
    {
        $this->travelTo('2026-11-15');

        $santri = $this->buatSantri();

        $nilaiAkademik = SantriDetailPresenter::detail($santri)['nilaiAkademik'];

        $this->assertFalse($nilaiAkademik['ada_data']);
    }
}
