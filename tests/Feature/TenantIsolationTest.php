<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(string $tag): Pesantren
    {
        return Pesantren::create([
            'nama_pesantren'      => "Pesantren {$tag}",
            'slug'                => "pesantren-" . strtolower($tag),
            'paket_langganan'     => 'rintisan',
            'max_santri_kuota'    => 100,
            'status_berlangganan' => 'active',
            'expired_at'          => now()->addYear(),
        ]);
    }

    private function makeUser(Pesantren $pesantren, string $role, string $tag): User
    {
        return User::create([
            'pesantren_id' => $pesantren->id,
            'name'         => "{$role} {$tag}",
            'email'        => strtolower(str_replace('_', '', $role)) . ".{$tag}@test.com",
            'password'     => bcrypt('password'),
            'role'         => $role,
        ]);
    }

    private function makeSantri(Pesantren $pesantren, User $wali, User $ustadz, string $nis): Santri
    {
        // Provide pesantren_id explicitly so the Multitenantable creating-hook
        // (which only overwrites when pesantren_id is empty) leaves it untouched,
        // regardless of any stale auth state from a previous test.
        return Santri::create([
            'pesantren_id'         => $pesantren->id,
            'wali_santri_id'       => $wali->id,
            'pembimbing_ustadz_id' => $ustadz->id,
            'nis'                  => $nis,
            'nama_lengkap'         => "Santri {$nis}",
            'kelas'                => '1A',
            'kamar'                => 'A',
        ]);
    }

    private function makeKelas(Pesantren $pesantren, string $nama): Kelas
    {
        return Kelas::create([
            'pesantren_id' => $pesantren->id,
            'nama_kelas'   => $nama,
        ]);
    }

    private function makeMataPelajaran(Pesantren $pesantren, Kelas $kelas, User $ustadz, string $nama): MataPelajaran
    {
        return MataPelajaran::create([
            'pesantren_id' => $pesantren->id,
            'kelas_id'     => $kelas->id,
            'ustadz_id'    => $ustadz->id,
            'nama_mapel'   => $nama,
        ]);
    }

    private function makeNilaiAkademik(Pesantren $pesantren, Santri $santri, MataPelajaran $mapel, int $nilai, string $periode = 'Semester_Ganjil'): NilaiAkademik
    {
        return NilaiAkademik::create([
            'pesantren_id'      => $pesantren->id,
            'santri_id'         => $santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran'      => '2026/2027',
            'periode'           => $periode,
            'nilai'             => $nilai,
        ]);
    }

    public function test_admin_pesantren_a_hanya_melihat_mata_pelajaran_pesantren_a(): void
    {
        $pesantrenA = $this->makePesantren('A');
        $pesantrenB = $this->makePesantren('B');

        $adminA  = $this->makeUser($pesantrenA, 'admin_pesantren', 'A');
        $ustadzA = $this->makeUser($pesantrenA, 'ustadz', 'A');
        $ustadzB = $this->makeUser($pesantrenB, 'ustadz', 'B');

        $kelasA = $this->makeKelas($pesantrenA, 'Kelas A');
        $kelasB = $this->makeKelas($pesantrenB, 'Kelas B');

        $this->makeMataPelajaran($pesantrenA, $kelasA, $ustadzA, 'Tafsir');
        $this->makeMataPelajaran($pesantrenA, $kelasA, $ustadzA, 'Hadits');
        $this->makeMataPelajaran($pesantrenB, $kelasB, $ustadzB, 'Fiqih');

        $this->actingAs($adminA);

        $mapel = MataPelajaran::all();

        $this->assertCount(2, $mapel);
        $mapel->each(fn($m) => $this->assertEquals($pesantrenA->id, $m->pesantren_id));
    }

    public function test_admin_pesantren_a_hanya_melihat_nilai_akademik_pesantren_a(): void
    {
        $pesantrenA = $this->makePesantren('A');
        $pesantrenB = $this->makePesantren('B');

        $adminA  = $this->makeUser($pesantrenA, 'admin_pesantren', 'A');
        $waliA   = $this->makeUser($pesantrenA, 'wali_santri', 'A');
        $ustadzA = $this->makeUser($pesantrenA, 'ustadz', 'A');
        $waliB   = $this->makeUser($pesantrenB, 'wali_santri', 'B');
        $ustadzB = $this->makeUser($pesantrenB, 'ustadz', 'B');

        $kelasA = $this->makeKelas($pesantrenA, 'Kelas A');
        $kelasB = $this->makeKelas($pesantrenB, 'Kelas B');

        $santriA = $this->makeSantri($pesantrenA, $waliA, $ustadzA, 'A001');
        $santriB = $this->makeSantri($pesantrenB, $waliB, $ustadzB, 'B001');

        $mapelA = $this->makeMataPelajaran($pesantrenA, $kelasA, $ustadzA, 'Tafsir');
        $mapelB = $this->makeMataPelajaran($pesantrenB, $kelasB, $ustadzB, 'Fiqih');

        $this->makeNilaiAkademik($pesantrenA, $santriA, $mapelA, 88, 'Semester_Ganjil');
        $this->makeNilaiAkademik($pesantrenA, $santriA, $mapelA, 90, 'Semester_Genap');
        $this->makeNilaiAkademik($pesantrenB, $santriB, $mapelB, 75, 'Semester_Ganjil');

        $this->actingAs($adminA);

        $nilai = NilaiAkademik::all();

        $this->assertCount(2, $nilai);
        $nilai->each(fn($n) => $this->assertEquals($pesantrenA->id, $n->pesantren_id));
    }

    public function test_admin_pesantren_a_hanya_melihat_santri_pesantren_a(): void
    {
        $pesantrenA = $this->makePesantren('A');
        $pesantrenB = $this->makePesantren('B');

        $adminA  = $this->makeUser($pesantrenA, 'admin_pesantren', 'A');
        $waliA   = $this->makeUser($pesantrenA, 'wali_santri', 'A');
        $ustadzA = $this->makeUser($pesantrenA, 'ustadz', 'A');
        $waliB   = $this->makeUser($pesantrenB, 'wali_santri', 'B');
        $ustadzB = $this->makeUser($pesantrenB, 'ustadz', 'B');

        // Insert 2 santri per pesantren without auth so INSERT is not scoped.
        $this->makeSantri($pesantrenA, $waliA, $ustadzA, 'A001');
        $this->makeSantri($pesantrenA, $waliA, $ustadzA, 'A002');
        $this->makeSantri($pesantrenB, $waliB, $ustadzB, 'B001');
        $this->makeSantri($pesantrenB, $waliB, $ustadzB, 'B002');

        // actingAs sets auth()->user() → Multitenantable global scope activates.
        $this->actingAs($adminA);

        $santri = Santri::all();

        $this->assertCount(2, $santri);
        $santri->each(fn($s) => $this->assertEquals($pesantrenA->id, $s->pesantren_id));
    }

    public function test_admin_pesantren_b_hanya_melihat_santri_pesantren_b(): void
    {
        $pesantrenA = $this->makePesantren('A');
        $pesantrenB = $this->makePesantren('B');

        $adminB  = $this->makeUser($pesantrenB, 'admin_pesantren', 'B');
        $waliA   = $this->makeUser($pesantrenA, 'wali_santri', 'A');
        $ustadzA = $this->makeUser($pesantrenA, 'ustadz', 'A');
        $waliB   = $this->makeUser($pesantrenB, 'wali_santri', 'B');
        $ustadzB = $this->makeUser($pesantrenB, 'ustadz', 'B');

        $this->makeSantri($pesantrenA, $waliA, $ustadzA, 'A001');
        $this->makeSantri($pesantrenA, $waliA, $ustadzA, 'A002');
        $this->makeSantri($pesantrenB, $waliB, $ustadzB, 'B001');
        $this->makeSantri($pesantrenB, $waliB, $ustadzB, 'B002');

        $this->actingAs($adminB);

        $santri = Santri::all();

        $this->assertCount(2, $santri);
        $santri->each(fn($s) => $this->assertEquals($pesantrenB->id, $s->pesantren_id));
    }
}
