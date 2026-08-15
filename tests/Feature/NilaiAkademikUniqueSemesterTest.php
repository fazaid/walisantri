<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\Pesantren;
use App\Models\Santri;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unique lima kolom (santri_id, mata_pelajaran_id, tahun_ajaran, periode, bulan)
 * TIDAK menjaga apa pun untuk periode semester, karena di sana `bulan` NULL dan
 * NULL tidak pernah sama dengan NULL di dalam UNIQUE. Partial unique index
 * `nilai_akademik_unik_tanpa_bulan` menutup celah itu; tes ini yang menahannya
 * supaya tidak hilang lagi saat skema disentuh berikutnya.
 */
class NilaiAkademikUniqueSemesterTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: int, 1: int} [santriId, mapelId] */
    private function siapkan(): array
    {
        $pesantren = Pesantren::factory()->create();
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        return [$santri->id, $mapel->id];
    }

    private function baris(int $santriId, int $mapelId, ?string $bulan, int $nilai): array
    {
        return [
            'pesantren_id' => Santri::find($santriId)->pesantren_id,
            'santri_id' => $santriId,
            'mata_pelajaran_id' => $mapelId,
            'tahun_ajaran' => '2026/2027',
            'periode' => $bulan === null ? 'Semester_Ganjil' : 'Bulanan',
            'bulan' => $bulan,
            'nilai' => $nilai,
        ];
    }

    public function test_nilai_semester_ganda_ditolak_database(): void
    {
        [$santriId, $mapelId] = $this->siapkan();

        NilaiAkademik::create($this->baris($santriId, $mapelId, null, 90));

        $this->expectException(UniqueConstraintViolationException::class);

        // Sebelum partial index dipasang, baris kedua ini lolos tanpa suara dan
        // rata-rata rapor santri jadi (90+60)/2 — bukan 90 atau 60, melainkan
        // angka yang tidak pernah diinput siapa pun.
        NilaiAkademik::create($this->baris($santriId, $mapelId, null, 60));
    }

    public function test_semester_ganjil_dan_genap_tetap_boleh_berdampingan(): void
    {
        [$santriId, $mapelId] = $this->siapkan();

        NilaiAkademik::create($this->baris($santriId, $mapelId, null, 90));

        $genap = $this->baris($santriId, $mapelId, null, 80);
        $genap['periode'] = 'Semester_Genap';
        NilaiAkademik::create($genap);

        $this->assertSame(2, NilaiAkademik::count());
    }

    public function test_nilai_bulanan_tidak_terpengaruh_partial_index(): void
    {
        [$santriId, $mapelId] = $this->siapkan();

        NilaiAkademik::create($this->baris($santriId, $mapelId, '9-2026', 90));
        NilaiAkademik::create($this->baris($santriId, $mapelId, '10-2026', 80));

        $this->assertSame(2, NilaiAkademik::count());
    }
}
