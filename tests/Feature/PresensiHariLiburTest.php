<?php

namespace Tests\Feature;

use App\Filament\Pages\PresensiHarianPage;
use App\Filament\Resources\PresensiHariLiburs\Pages\ListPresensiHariLiburs;
use App\Filament\Resources\PresensiHariLiburs\PresensiHariLiburResource;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\PresensiHariLibur;
use App\Models\PresensiPengaturan;
use App\Models\Santri;
use App\Models\User;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class PresensiHariLiburTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Pesantren, 1: User} */
    private function siapkan(): array
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        return [$pesantren, $admin];
    }

    private function tambah(User $admin, string $mulai, string $selesai, string $keterangan = 'Libur Uji'): Testable
    {
        return Livewire::actingAs($admin)
            ->test(ListPresensiHariLiburs::class)
            ->callAction(CreateAction::class, data: [
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'keterangan' => $keterangan,
                'tahun_ajaran' => '2026/2027',
            ]);
    }

    public function test_rentang_dipecah_menjadi_baris_harian(): void
    {
        [, $admin] = $this->siapkan();

        // 7–13 September 2026 = 7 hari, inklusif kedua ujungnya.
        $this->tambah($admin, '2026-09-07', '2026-09-13', 'Libur Akhir Semester')
            ->assertHasNoActionErrors();

        $this->assertSame(7, PresensiHariLibur::count());
        $this->assertDatabaseHas('presensi_hari_libur', [
            'tanggal' => '2026-09-07',
            'keterangan' => 'Libur Akhir Semester',
        ]);
        $this->assertDatabaseHas('presensi_hari_libur', ['tanggal' => '2026-09-13']);
    }

    public function test_libur_sehari_cukup_isi_tanggal_yang_sama(): void
    {
        [, $admin] = $this->siapkan();

        $this->tambah($admin, '2026-09-15', '2026-09-15', 'Maulid Nabi')
            ->assertHasNoActionErrors();

        $this->assertSame(1, PresensiHariLibur::count());
    }

    public function test_rentang_beririsan_memperbarui_keterangan_bukan_gagal(): void
    {
        [, $admin] = $this->siapkan();

        $this->tambah($admin, '2026-09-07', '2026-09-09', 'Keterangan Lama');
        $this->tambah($admin, '2026-09-08', '2026-09-10', 'Keterangan Baru')
            ->assertHasNoActionErrors();

        // 7, 8, 9, 10 = 4 hari unik. Tanpa updateOrCreate, penyimpanan kedua akan
        // melanggar unique (pesantren_id, tanggal) dan errornya mentah ke layar.
        $this->assertSame(4, PresensiHariLibur::count());
        $this->assertSame('Keterangan Lama', PresensiHariLibur::whereDate('tanggal', '2026-09-07')->value('keterangan'));
        $this->assertSame('Keterangan Baru', PresensiHariLibur::whereDate('tanggal', '2026-09-08')->value('keterangan'));
    }

    public function test_tanggal_terbalik_tetap_tersimpan_benar(): void
    {
        [, $admin] = $this->siapkan();

        // Admin salah membalik urutan — ditukar diam-diam, bukan menyimpan nol baris.
        $this->tambah($admin, '2026-09-10', '2026-09-08')->assertHasNoActionErrors();

        $this->assertSame(3, PresensiHariLibur::count());
    }

    public function test_rentang_kelewat_panjang_ditolak(): void
    {
        [, $admin] = $this->siapkan();

        // Salah ketik tahun adalah cara paling mudah membuat rentang raksasa.
        $this->tambah($admin, '2026-09-01', '2036-09-01');

        $this->assertSame(0, PresensiHariLibur::count());
    }

    public function test_hanya_admin_pesantren_yang_bisa_mengelola_hari_libur(): void
    {
        [$pesantren, $admin] = $this->siapkan();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($admin);
        $this->assertTrue(PresensiHariLiburResource::canViewAny());

        $this->actingAs($ustadz);
        $this->assertFalse(PresensiHariLiburResource::canViewAny());
    }

    public function test_halaman_isi_presensi_memperingatkan_tanpa_melarang(): void
    {
        [$pesantren, $admin] = $this->siapkan();

        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);
        PresensiPengaturan::untuk($pesantren->id)->update(['hari_libur_mingguan' => []]);

        PresensiHariLibur::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $pesantren->id,
            'tanggal' => now()->subDays(3)->toDateString(),
            'keterangan' => 'Maulid Nabi',
            'tahun_ajaran' => '2026/2027',
        ]);

        Livewire::actingAs($admin)
            ->test(PresensiHarianPage::class)
            ->set('tanggal', now()->subDays(3)->toDateString())
            ->assertSee('Tanggal ini hari libur')
            ->assertSee('Maulid Nabi')
            // MEMPERINGATKAN, bukan melarang: ada pondok yang tetap berkegiatan
            // di hari libur, dan melarang akan memaksa mereka memakai tanggal salah.
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Presensi::count());
    }

    public function test_hari_biasa_tidak_memunculkan_peringatan(): void
    {
        [$pesantren, $admin] = $this->siapkan();

        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);
        Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);
        PresensiPengaturan::untuk($pesantren->id)->update(['hari_libur_mingguan' => []]);

        Livewire::actingAs($admin)
            ->test(PresensiHarianPage::class)
            ->set('tanggal', now()->subDay()->toDateString())
            ->assertDontSee('Tanggal ini hari libur');
    }
}
