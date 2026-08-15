<?php

namespace Tests\Feature;

use App\Filament\Pages\PresensiHarianPage;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\PresensiPengaturan;
use App\Models\Santri;
use App\Models\User;
use App\Support\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Jendela edit adalah konsep penguncian periode PERTAMA di aplikasi ini — sampai
 * v4.25 tidak ada satu pun minDate() di luar panel super admin, sehingga ustadz
 * bisa menimpa data tiga bulan lalu tanpa jejak. Presensi tidak boleh mewarisi
 * kelonggaran itu karena wali santri membacanya.
 *
 * Yang diuji di sini terutama LAPIS KEDUA: penolakan di save(). minDate pada
 * DatePicker hanya menjaga UI dan bisa dilewati request Livewire yang dirakit
 * tangan — tes ini memang memanggil save() langsung dengan tanggal lampau.
 */
class PresensiJendelaEditTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Pesantren, 1: User, 2: User} [pesantren, ustadzWaliKelas, admin] */
    private function siapkan(): array
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $kelas = Kelas::factory()->create([
            'pesantren_id' => $pesantren->id,
            'wali_kelas_id' => $ustadz->id,
        ]);

        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
        ]);

        PresensiPengaturan::untuk($pesantren->id)->update(['batas_edit_ustadz_hari' => 7]);

        return [$pesantren, $ustadz, $admin];
    }

    private function tanggalLampau(int $hariLalu): string
    {
        return Waktu::sekarang()->subDays($hariLalu)->toDateString();
    }

    public function test_ustadz_ditolak_untuk_tanggal_di_luar_batas(): void
    {
        [, $ustadz] = $this->siapkan();

        Livewire::actingAs($ustadz)
            ->test(PresensiHarianPage::class)
            ->set('tanggal', $this->tanggalLampau(30))
            ->call('save');

        // Lewat UI, ->minDate() di DatePicker sudah menangkapnya lebih dulu; yang
        // penting di sini adalah tidak ada baris yang lolos tersimpan.
        $this->assertSame(0, Presensi::count());
    }

    public function test_lapis_kedua_menolak_tanggal_lampau_tanpa_melewati_form(): void
    {
        [, $ustadz] = $this->siapkan();

        $halaman = Livewire::actingAs($ustadz)
            ->test(PresensiHarianPage::class)
            ->instance();

        // Memanggil predikat penegaknya langsung — meniru request Livewire yang
        // dirakit tangan, yang tidak pernah melewati validasi form sama sekali.
        // Kalau lapis ini hilang, jalur itu terbuka lebar tanpa ada yang menahannya.
        $this->assertFalse($halaman->tanggalDalamJendelaEdit($this->tanggalLampau(30)));
        $this->assertTrue($halaman->tanggalDalamJendelaEdit($this->tanggalLampau(3)));
        $this->assertTrue($halaman->tanggalDalamJendelaEdit(Waktu::hariIni()));
    }

    public function test_lapis_kedua_tidak_membatasi_admin(): void
    {
        [, , $admin] = $this->siapkan();

        $halaman = Livewire::actingAs($admin)
            ->test(PresensiHarianPage::class)
            ->instance();

        $this->assertTrue($halaman->tanggalDalamJendelaEdit($this->tanggalLampau(365)));
    }

    public function test_ustadz_diterima_untuk_tanggal_di_dalam_batas(): void
    {
        [, $ustadz] = $this->siapkan();

        Livewire::actingAs($ustadz)
            ->test(PresensiHarianPage::class)
            ->set('tanggal', $this->tanggalLampau(3))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Presensi::count());
    }

    public function test_admin_tidak_terkena_batas_edit(): void
    {
        [, , $admin] = $this->siapkan();

        // Tanggal yang sama persis ditolak untuk ustadz di kasus pertama.
        Livewire::actingAs($admin)
            ->test(PresensiHarianPage::class)
            ->set('tanggal', $this->tanggalLampau(30))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Presensi::count());
    }

    public function test_batas_nol_berarti_ustadz_bebas_menyunting(): void
    {
        [$pesantren, $ustadz] = $this->siapkan();

        PresensiPengaturan::untuk($pesantren->id)->update(['batas_edit_ustadz_hari' => 0]);

        Livewire::actingAs($ustadz)
            ->test(PresensiHarianPage::class)
            ->set('tanggal', $this->tanggalLampau(90))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Presensi::count());
    }

    public function test_batas_hari_ini_masih_selalu_boleh_diisi(): void
    {
        [$pesantren, $ustadz] = $this->siapkan();

        // Batas 1 hari = hanya hari ini. Kasus tepi yang mudah meleset kalau
        // perhitungannya memakai subDays($n) alih-alih subDays($n - 1).
        PresensiPengaturan::untuk($pesantren->id)->update(['batas_edit_ustadz_hari' => 1]);

        Livewire::actingAs($ustadz)
            ->test(PresensiHarianPage::class)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Presensi::count());
        $this->assertSame(Waktu::hariIni(), Presensi::first()->tanggal->toDateString());
    }
}
