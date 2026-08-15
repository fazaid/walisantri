<?php

namespace Tests\Feature;

use App\Enums\StatusKehadiran;
use App\Filament\Pages\PresensiHarianPage;
use App\Filament\Resources\Presensis\PresensiResource;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kunci §5.4 — "penugasan di satu modul tidak membuka modul lain".
 *
 * Presensi harian dipegang WALI KELAS. Pembimbing halaqah sengaja nol akses:
 * halaqah adalah relasi pembinaan hafalan dan adab, bukan kehadiran kelas. Tes ini
 * ada supaya pelebaran cakupan di masa depan harus jadi keputusan sadar, bukan
 * efek samping refactor — sekelas PenugasanUstadzTest dan WaliRaporTest.
 */
class PresensiCakupanUstadzTest extends TestCase
{
    use RefreshDatabase;

    private function presensiUntuk(Santri $santri, ?Kelas $kelas = null): Presensi
    {
        return Presensi::create([
            'pesantren_id' => $santri->pesantren_id,
            'santri_id' => $santri->id,
            'tanggal' => now()->toDateString(),
            'jam_ke' => Presensi::HARIAN,
            'kelas_id' => $kelas?->id ?? $santri->kelas_id,
            'status' => StatusKehadiran::Hadir,
        ]);
    }

    public function test_wali_kelas_hanya_melihat_presensi_kelasnya(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $kelasnya = Kelas::factory()->create([
            'pesantren_id' => $pesantren->id,
            'nama_kelas' => 'Kelas Perwalian',
            'wali_kelas_id' => $ustadz->id,
        ]);
        $kelasLain = Kelas::factory()->create([
            'pesantren_id' => $pesantren->id,
            'nama_kelas' => 'Kelas Orang Lain',
        ]);

        $santriSendiri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelasnya->id]);
        $santriLain = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelasLain->id]);

        $milikSendiri = $this->presensiUntuk($santriSendiri);
        $milikOrangLain = $this->presensiUntuk($santriLain);

        $this->actingAs($ustadz);
        $terlihat = PresensiResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($terlihat->contains($milikSendiri->id));
        $this->assertFalse($terlihat->contains($milikOrangLain->id));
    }

    public function test_pembimbing_halaqah_tidak_melihat_presensi_apa_pun(): void
    {
        $pesantren = Pesantren::factory()->create();
        $pembimbing = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        // Santri binaannya — tapi ia BUKAN wali kelas mana pun.
        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'pembimbing_ustadz_id' => $pembimbing->id,
        ]);
        $this->presensiUntuk($santri);

        $this->actingAs($pembimbing);

        $this->assertSame(0, PresensiResource::getEloquentQuery()->count());
    }

    public function test_pembimbing_halaqah_tidak_bisa_mengisi_presensi(): void
    {
        $pesantren = Pesantren::factory()->create();
        $pembimbing = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'pembimbing_ustadz_id' => $pembimbing->id,
        ]);

        Livewire::actingAs($pembimbing)
            ->test(PresensiHarianPage::class)
            ->assertSee('Anda belum ditetapkan sebagai wali kelas.')
            ->call('save');

        $this->assertSame(0, Presensi::count());
    }

    public function test_route_binding_menolak_record_di_luar_cakupan(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        Kelas::factory()->create([
            'pesantren_id' => $pesantren->id,
            'nama_kelas' => 'Kelas Perwalian',
            'wali_kelas_id' => $ustadz->id,
        ]);
        $kelasLain = Kelas::factory()->create(['pesantren_id' => $pesantren->id, 'nama_kelas' => 'Kelas Lain']);

        $santriLain = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelasLain->id]);
        $presensiLain = $this->presensiUntuk($santriLain);

        $this->actingAs($ustadz);

        // Tanpa override route binding, ustadz bisa menjangkau record ini dengan
        // menebak URL meski ia tidak muncul di daftar.
        $this->assertNull(
            PresensiResource::getRecordRouteBindingEloquentQuery()->find($presensiLain->id)
        );
    }

    public function test_super_admin_tidak_bisa_mengakses_modul_presensi(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'pesantren_id' => null]);

        $this->actingAs($superAdmin);

        $this->assertFalse(PresensiResource::canViewAny());
        $this->assertFalse(PresensiHarianPage::canAccess());
    }

    public function test_ustadz_tidak_bisa_menghapus_presensi(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id, 'wali_kelas_id' => $ustadz->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'kelas_id' => $kelas->id]);
        $presensi = $this->presensiUntuk($santri);

        $this->actingAs($ustadz);
        $this->assertFalse(PresensiResource::canDelete($presensi));

        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $this->actingAs($admin);
        $this->assertTrue(PresensiResource::canDelete($presensi));
    }
}
