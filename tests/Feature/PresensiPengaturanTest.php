<?php

namespace Tests\Feature;

use App\Filament\Pages\PresensiPengaturanPage;
use App\Models\Pesantren;
use App\Models\PresensiPengaturan;
use App\Models\User;
use App\Services\ProvisionTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tiga lapis pengisi baris pengaturan diuji di sini. Ketiganya disengaja: modul
 * Mutaba'ah pernah lumpuh diam-diam berbulan-bulan karena satu-satunya pengisi
 * datanya adalah migrasi yang hanya jalan sekali (PRD §22, kelas bug v4.21).
 */
class PresensiPengaturanTest extends TestCase
{
    use RefreshDatabase;

    public function test_provision_tenant_membuat_baris_pengaturan(): void
    {
        $pesantren = Pesantren::factory()->create();

        // Factory tidak melewati ProvisionTenant, jadi barisnya memang belum ada.
        PresensiPengaturan::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->delete();

        app(ProvisionTenant::class)->jalankan($pesantren);

        $this->assertDatabaseHas('presensi_pengaturan', [
            'pesantren_id' => $pesantren->id,
            'batas_edit_ustadz_hari' => 7,
        ]);
    }

    public function test_provision_tenant_idempoten(): void
    {
        $pesantren = Pesantren::factory()->create();

        app(ProvisionTenant::class)->jalankan($pesantren);
        app(ProvisionTenant::class)->jalankan($pesantren);

        $this->assertSame(1, PresensiPengaturan::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->count());
    }

    public function test_untuk_menyembuhkan_tenant_yang_barisnya_hilang(): void
    {
        $pesantren = Pesantren::factory()->create();

        PresensiPengaturan::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->delete();

        $pengaturan = PresensiPengaturan::untuk($pesantren->id);

        $this->assertSame($pesantren->id, $pengaturan->pesantren_id);
        $this->assertSame(15, $pengaturan->toleransi_terlambat_menit);
        // Default [0] = libur tiap Minggu (Carbon::dayOfWeek, bukan ISO-8601).
        $this->assertSame([0], $pengaturan->hari_libur_mingguan);
    }

    public function test_hanya_admin_pesantren_yang_bisa_membuka_pengaturan(): void
    {
        $pesantren = Pesantren::factory()->create();

        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($admin);
        $this->assertTrue(PresensiPengaturanPage::canAccess());

        $this->actingAs($ustadz);
        $this->assertFalse(PresensiPengaturanPage::canAccess());
    }

    public function test_admin_bisa_menyimpan_pengaturan(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        Livewire::actingAs($admin)
            ->test(PresensiPengaturanPage::class)
            ->set('jam_masuk', '06:30')
            ->set('toleransi_terlambat_menit', 10)
            ->set('batas_edit_ustadz_hari', 3)
            ->set('hari_libur_mingguan', ['5'])
            ->call('save')
            ->assertHasNoErrors();

        $pengaturan = PresensiPengaturan::untuk($pesantren->id)->fresh();

        $this->assertSame(10, $pengaturan->toleransi_terlambat_menit);
        $this->assertSame(3, $pengaturan->batas_edit_ustadz_hari);
        // Disimpan sebagai integer, bukan string '5' — supaya perbandingan dengan
        // Carbon::dayOfWeek nanti tidak bergantung tipe.
        $this->assertSame([5], $pengaturan->hari_libur_mingguan);
    }

    public function test_batas_awal_edit_nol_berarti_tanpa_batas(): void
    {
        $pesantren = Pesantren::factory()->create();
        $pengaturan = PresensiPengaturan::untuk($pesantren->id);

        $pengaturan->update(['batas_edit_ustadz_hari' => 0]);
        $this->assertNull($pengaturan->fresh()->batasAwalEditUstadz());

        $pengaturan->update(['batas_edit_ustadz_hari' => 7]);
        $this->assertNotNull($pengaturan->fresh()->batasAwalEditUstadz());
    }
}
