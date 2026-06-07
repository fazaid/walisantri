<?php

namespace Tests\Feature;

use App\Filament\Resources\NilaiAkademiks\NilaiAkademikResource;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NilaiAkademikTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(): Pesantren
    {
        return Pesantren::factory()->create([
            'paket_langganan'     => 'gratis',
            'status_berlangganan' => 'active',
            'expired_at'          => now()->addYear(),
        ]);
    }

    public function test_kombinasi_santri_mapel_periode_tahun_ajaran_harus_unik(): void
    {
        $pesantren = $this->makePesantren();
        $kelas     = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $santri    = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);
        $mapel     = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id'     => $kelas->id,
        ]);

        $atribut = [
            'pesantren_id'      => $pesantren->id,
            'santri_id'         => $santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran'      => '2026/2027',
            'periode'           => 'Semester_Ganjil',
            'nilai'             => 90,
        ];

        NilaiAkademik::create($atribut);

        $this->expectException(QueryException::class);

        // Nilai kedua untuk kombinasi santri+mapel+periode+tahun ajaran yang sama harus ditolak DB.
        NilaiAkademik::create([...$atribut, 'nilai' => 75]);
    }

    public function test_ustadz_hanya_melihat_nilai_untuk_mapel_yang_diampu(): void
    {
        $pesantren = $this->makePesantren();
        $kelas     = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        $ustadzA = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $ustadzB = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $mapelA = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id'     => $kelas->id,
            'ustadz_id'    => $ustadzA->id,
            'nama_mapel'   => 'Tafsir',
        ]);
        $mapelB = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id'     => $kelas->id,
            'ustadz_id'    => $ustadzB->id,
            'nama_mapel'   => 'Hadits',
        ]);

        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);

        $nilaiA = NilaiAkademik::create([
            'pesantren_id'      => $pesantren->id,
            'santri_id'         => $santri->id,
            'mata_pelajaran_id' => $mapelA->id,
            'tahun_ajaran'      => '2026/2027',
            'periode'           => 'Semester_Ganjil',
            'nilai'             => 88,
        ]);
        $nilaiB = NilaiAkademik::create([
            'pesantren_id'      => $pesantren->id,
            'santri_id'         => $santri->id,
            'mata_pelajaran_id' => $mapelB->id,
            'tahun_ajaran'      => '2026/2027',
            'periode'           => 'Semester_Ganjil',
            'nilai'             => 76,
        ]);

        $this->actingAs($ustadzA);

        $hasil = NilaiAkademikResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($hasil->contains($nilaiA->id));
        $this->assertFalse($hasil->contains($nilaiB->id));
    }

    public function test_admin_pesantren_melihat_semua_nilai_di_pesantrennya(): void
    {
        $pesantren = $this->makePesantren();
        $kelas     = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $admin     = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $mapelA = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id'     => $kelas->id,
            'nama_mapel'   => 'Bahasa Arab',
        ]);
        $mapelB = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id'     => $kelas->id,
            'nama_mapel'   => 'Akidah Akhlak',
        ]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);

        $nilaiA = NilaiAkademik::create([
            'pesantren_id'      => $pesantren->id,
            'santri_id'         => $santri->id,
            'mata_pelajaran_id' => $mapelA->id,
            'tahun_ajaran'      => '2026/2027',
            'periode'           => 'Bulanan',
            'nilai'             => 80,
        ]);
        $nilaiB = NilaiAkademik::create([
            'pesantren_id'      => $pesantren->id,
            'santri_id'         => $santri->id,
            'mata_pelajaran_id' => $mapelB->id,
            'tahun_ajaran'      => '2026/2027',
            'periode'           => 'Bulanan',
            'nilai'             => 92,
        ]);

        $this->actingAs($admin);

        $hasil = NilaiAkademikResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($hasil->contains($nilaiA->id));
        $this->assertTrue($hasil->contains($nilaiB->id));
    }
}
