<?php

namespace Tests\Feature;

use App\Enums\StatusKehadiran;
use App\Filament\Pages\PresensiHarianPage;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\Santri;
use App\Models\User;
use App\Support\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PresensiHarianPageTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Pesantren, 1: User, 2: Kelas} */
    private function siapkan(int $jumlahSantri = 2): array
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        // ⚠️ SantriFactory TIDAK mengisi kelas_id — tanpa baris ini santri lahir
        // tanpa kelas dan grid per-kelas akan kosong, sehingga tesnya lulus tanpa
        // menguji apa pun.
        for ($i = 0; $i < $jumlahSantri; $i++) {
            Santri::factory()->create([
                'pesantren_id' => $pesantren->id,
                'kelas_id' => $kelas->id,
                'nama_lengkap' => 'Santri '.$i,
            ]);
        }

        return [$pesantren, $admin, $kelas];
    }

    public function test_admin_bisa_menyimpan_presensi_satu_kelas(): void
    {
        [$pesantren, $admin, $kelas] = $this->siapkan(3);

        Livewire::actingAs($admin)
            ->test(PresensiHarianPage::class)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, Presensi::count());
        // Prefill Hadir: menekan simpan berarti hari itu ditutup oleh manusia.
        $this->assertSame(StatusKehadiran::Hadir, Presensi::first()->status);
        // kelas_id disimpan sebagai snapshot saat presensi dicatat.
        $this->assertSame($kelas->id, Presensi::first()->kelas_id);
        $this->assertSame($admin->id, Presensi::first()->dicatat_oleh);
    }

    public function test_simpan_ulang_memperbarui_bukan_menduplikasi(): void
    {
        [, $admin] = $this->siapkan(2);

        $komponen = Livewire::actingAs($admin)->test(PresensiHarianPage::class);
        $komponen->call('save')->assertHasNoErrors();

        $rows = $komponen->get('rows');
        $kunci = array_key_first($rows);
        $rows[$kunci]['status'] = StatusKehadiran::Alpa->value;

        $komponen->set('rows', $rows)->call('save')->assertHasNoErrors();

        // upsert: ON CONFLICT (santri_id, tanggal, jam_ke) DO UPDATE.
        $this->assertSame(2, Presensi::count());
        $this->assertSame(1, Presensi::where('status', StatusKehadiran::Alpa->value)->count());
    }

    public function test_kelas_snapshot_tidak_ikut_berubah_saat_santri_pindah_kelas(): void
    {
        [$pesantren, $admin, $kelas] = $this->siapkan(1);

        Livewire::actingAs($admin)->test(PresensiHarianPage::class)->call('save');

        $kelasBaru = Kelas::factory()->create([
            'pesantren_id' => $pesantren->id,
            'nama_kelas' => 'Kelas Pindahan',
        ]);
        Santri::first()->update(['kelas_id' => $kelasBaru->id]);

        // Rekap per kelas harus mencerminkan kelas SAAT presensi dicatat.
        $this->assertSame($kelas->id, Presensi::first()->kelas_id);
    }

    public function test_mode_semua_santri_dan_tanpa_kelas_hanya_untuk_admin(): void
    {
        [$pesantren, $admin, $kelas] = $this->siapkan(1);
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $kelas->update(['wali_kelas_id' => $ustadz->id]);

        $opsiAdmin = Livewire::actingAs($admin)
            ->test(PresensiHarianPage::class)
            ->instance()->form->getComponent('kelompok')->getOptions();

        $this->assertArrayHasKey(PresensiHarianPage::KELOMPOK_SEMUA, $opsiAdmin);
        $this->assertArrayHasKey(PresensiHarianPage::KELOMPOK_TANPA_KELAS, $opsiAdmin);

        $opsiUstadz = Livewire::actingAs($ustadz)
            ->test(PresensiHarianPage::class)
            ->instance()->form->getComponent('kelompok')->getOptions();

        $this->assertSame([PresensiHarianPage::KELOMPOK_KELAS], array_keys($opsiUstadz));
    }

    public function test_mode_tanpa_kelas_menjangkau_santri_yang_kelasnya_kosong(): void
    {
        [$pesantren, $admin] = $this->siapkan(1);

        // Tiga jalur bisa menghasilkan santri tanpa kelas (form, impor massal,
        // nullOnDelete saat kelas dihapus). Tanpa mode ini mereka tak terjangkau.
        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => null,
            'nama_lengkap' => 'Santri Yatim Kelas',
        ]);

        Livewire::actingAs($admin)
            ->test(PresensiHarianPage::class)
            ->set('kelompok', PresensiHarianPage::KELOMPOK_TANPA_KELAS)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Presensi::count());
        $this->assertNull(Presensi::first()->kelas_id);
    }

    public function test_tanggal_memakai_jam_dinding_wib_bukan_utc(): void
    {
        [, $admin] = $this->siapkan(1);

        // 01.00 WIB = 18.00 UTC HARI SEBELUMNYA. Memakai now()->toDateString()
        // mentah akan mencatat presensi mundur satu hari.
        Carbon::setTestNow(Carbon::parse('2026-09-10 18:00:00', 'UTC'));

        Livewire::actingAs($admin)->test(PresensiHarianPage::class)->call('save');

        $this->assertSame('2026-09-11', Presensi::first()->tanggal->toDateString());
        $this->assertSame('2026-09-11', Waktu::hariIni());

        Carbon::setTestNow();
    }

    public function test_ustadz_tanpa_perwalian_mendapat_arahan_bukan_halaman_kosong(): void
    {
        [$pesantren] = $this->siapkan(1);
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        Livewire::actingAs($ustadz)
            ->test(PresensiHarianPage::class)
            ->assertSee('Anda belum ditetapkan sebagai wali kelas.')
            ->assertSee('Santri → Kelas');
    }

    public function test_pesantren_tanpa_santri_aktif_diberi_tahu(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        Livewire::actingAs($admin)
            ->test(PresensiHarianPage::class)
            ->assertSee('Belum ada santri aktif yang bisa diabsen.');
    }
}
