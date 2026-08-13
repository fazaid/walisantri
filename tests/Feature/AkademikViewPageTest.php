<?php

namespace Tests\Feature;

use App\Filament\Resources\EkskulMasters\Pages\ViewEkskulMaster;
use App\Filament\Resources\MataPelajarans\Pages\ViewMataPelajaran;
use App\Filament\Resources\NilaiAkademiks\Pages\ViewNilaiAkademik;
use App\Filament\Resources\SantriEkskuls\Pages\ViewSantriEkskul;
use App\Models\EkskulMaster;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\SantriEkskul;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cluster Akademik sebelumnya tidak punya halaman View sama sekali, sehingga
 * nilai_akademik.catatan dan ekskul_masters.deskripsi terisi lewat form tapi
 * tidak pernah bisa dibaca kembali.
 */
class AkademikViewPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_nilai_akademik_menampilkan_catatan(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);
        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        $nilai = NilaiAkademik::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Semester_Ganjil',
            'nilai' => 88,
            'catatan' => 'Perlu memperbanyak latihan iʼrab.',
        ]);

        Livewire::actingAs($admin)
            ->test(ViewNilaiAkademik::class, ['record' => $nilai->getRouteKey()])
            ->assertOk()
            ->assertSee('Perlu memperbanyak latihan iʼrab.');
    }

    public function test_view_ekskul_master_menampilkan_deskripsi(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $ekskul = EkskulMaster::create([
            'pesantren_id' => $pesantren->id,
            'nama' => 'Panahan',
            'pengajar' => 'Ustadz Hamzah',
            'deskripsi' => 'Latihan rutin setiap Sabtu pagi di lapangan utama.',
            'aktif' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(ViewEkskulMaster::class, ['record' => $ekskul->getRouteKey()])
            ->assertOk()
            ->assertSee('Latihan rutin setiap Sabtu pagi di lapangan utama.');
    }

    public function test_view_santri_ekskul_bisa_dirender(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'nama_lengkap' => 'Abdurrahman',
        ]);
        $ekskul = EkskulMaster::create([
            'pesantren_id' => $pesantren->id,
            'nama' => 'Kaligrafi',
            'aktif' => true,
        ]);

        $record = SantriEkskul::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'ekskul_id' => $ekskul->id,
            'level' => 'menengah',
            'tanggal_mulai' => now()->subMonth(),
            'aktif' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(ViewSantriEkskul::class, ['record' => $record->getRouteKey()])
            ->assertOk()
            ->assertSee('Abdurrahman')
            ->assertSee('Kaligrafi')
            ->assertSee('Menengah');
    }

    public function test_view_mata_pelajaran_bisa_dirender(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id, 'nama_kelas' => 'VII A']);
        $ustadz = User::factory()->ustadz()->create([
            'pesantren_id' => $pesantren->id,
            'name' => 'Ustadz Salman',
        ]);

        $mapel = MataPelajaran::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'ustadz_id' => $ustadz->id,
            'nama_mapel' => 'Sharaf',
        ]);

        Livewire::actingAs($admin)
            ->test(ViewMataPelajaran::class, ['record' => $mapel->getRouteKey()])
            ->assertOk()
            ->assertSee('Sharaf')
            ->assertSee('VII A')
            ->assertSee('Ustadz Salman');
    }
}
