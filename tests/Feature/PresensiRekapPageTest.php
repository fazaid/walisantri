<?php

namespace Tests\Feature;

use App\Enums\StatusKehadiran;
use App\Filament\Pages\PresensiRekapPage;
use App\Filament\Widgets\PresensiHariIniStat;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\PresensiHariLibur;
use App\Models\PresensiPengaturan;
use App\Models\Santri;
use App\Models\User;
use App\Support\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PresensiRekapPageTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Pesantren, 1: User, 2: Kelas} */
    private function siapkan(): array
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        PresensiPengaturan::untuk($pesantren->id)->update(['hari_libur_mingguan' => []]);

        return [$pesantren, $admin, $kelas];
    }

    private function catat(Santri $santri, string $tanggal, StatusKehadiran $status): void
    {
        Presensi::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $santri->pesantren_id,
            'santri_id' => $santri->id,
            'kelas_id' => $santri->kelas_id,
            'tanggal' => $tanggal,
            'jam_ke' => Presensi::HARIAN,
            'status' => $status,
        ]);
    }

    public function test_halaman_rekap_menampilkan_santri_dan_persentase(): void
    {
        [$pesantren, $admin, $kelas] = $this->siapkan();

        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nama_lengkap' => 'Ahmad Fauzi',
        ]);

        $this->catat($santri, Waktu::hariIni(), StatusKehadiran::Hadir);

        Livewire::actingAs($admin)
            ->test(PresensiRekapPage::class)
            ->set('periode', 'Bulanan')
            ->set('bulan', Waktu::sekarang()->month.'-'.Waktu::sekarang()->year)
            ->assertSee('Ahmad Fauzi')
            ->assertSee('% Hadir');
    }

    public function test_panel_perlu_perhatian_muncul_saat_ada_alpa_beruntun(): void
    {
        [$pesantren, $admin, $kelas] = $this->siapkan();

        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nama_lengkap' => 'Santri Bolos',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $this->catat($santri, Waktu::sekarang()->subDays($i)->toDateString(), StatusKehadiran::Alpa);
        }

        Livewire::actingAs($admin)
            ->test(PresensiRekapPage::class)
            ->set('periode', 'Bulanan')
            ->set('bulan', Waktu::sekarang()->month.'-'.Waktu::sekarang()->year)
            ->assertSee('Perlu Perhatian')
            ->assertSee('Santri Bolos');
    }

    public function test_ustadz_hanya_melihat_kelas_perwaliannya_di_rekap(): void
    {
        [$pesantren, , $kelas] = $this->siapkan();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $kelas->update(['wali_kelas_id' => $ustadz->id]);

        $kelasLain = Kelas::factory()->create(['pesantren_id' => $pesantren->id, 'nama_kelas' => 'Kelas Lain']);

        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nama_lengkap' => 'Santri Perwalian',
        ]);
        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelasLain->id,
            'nama_lengkap' => 'Santri Kelas Lain',
        ]);

        Livewire::actingAs($ustadz)
            ->test(PresensiRekapPage::class)
            ->set('periode', 'Bulanan')
            ->set('bulan', Waktu::sekarang()->month.'-'.Waktu::sekarang()->year)
            ->assertSee('Santri Perwalian')
            ->assertDontSee('Santri Kelas Lain');
    }

    public function test_widget_menghitung_hadir_dan_kelas_belum_diabsen(): void
    {
        [$pesantren, $admin, $kelas] = $this->siapkan();
        $kelasKedua = Kelas::factory()->create(['pesantren_id' => $pesantren->id, 'nama_kelas' => 'Kelas Kedua']);

        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);
        Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelasKedua->id]);

        $this->catat($santri, Waktu::hariIni(), StatusKehadiran::Hadir);

        Livewire::actingAs($admin)
            ->test(PresensiHariIniStat::class)
            ->assertSee('Hadir Hari Ini')
            ->assertSee('Kelas Belum Diabsen')
            // Dua kelas, satu sudah diabsen.
            ->assertSee('Dari 2 kelas');
    }

    public function test_widget_menjelaskan_keadaan_saat_hari_libur(): void
    {
        [$pesantren, $admin, $kelas] = $this->siapkan();
        Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);

        PresensiHariLibur::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $pesantren->id,
            'tanggal' => Waktu::hariIni(),
            'keterangan' => 'Maulid Nabi',
            'tahun_ajaran' => '2026/2027',
        ]);

        // "Belum diabsen" di hari libur bukan kelalaian — widgetnya menjelaskan
        // keadaan alih-alih menuduh.
        Livewire::actingAs($admin)
            ->test(PresensiHariIniStat::class)
            ->assertSee('Hari Ini Libur')
            ->assertSee('Maulid Nabi')
            ->assertDontSee('Kelas Belum Diabsen');
    }

    public function test_wali_santri_tidak_bisa_membuka_rekap(): void
    {
        $pesantren = Pesantren::factory()->create();
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($wali);

        $this->assertFalse(PresensiRekapPage::canAccess());
        $this->assertFalse(PresensiHariIniStat::canView());
    }
}
