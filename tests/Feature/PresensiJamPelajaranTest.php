<?php

namespace Tests\Feature;

use App\Filament\Resources\PresensiJamPelajarans\Pages\ListPresensiJamPelajarans;
use App\Filament\Resources\PresensiJamPelajarans\PresensiJamPelajaranResource;
use App\Models\Pesantren;
use App\Models\PresensiJamPelajaran;
use App\Models\User;
use App\Services\ProvisionTenant;
use App\Support\PresensiDefault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Master jam pelajaran — tiga lapis pengisi, sepola pengaturan presensi.
 *
 * Daftar bawaannya sengaja hidup di App\Support\PresensiDefault, BUKAN di dalam
 * migrasi: migrasi hanya jalan sekali, dan pesantren yang mendaftar sesudahnya
 * tidak akan pernah kebagian. Itu persis yang melumpuhkan modul Mutaba'ah
 * berbulan-bulan (PRD §22, kelas bug v4.21).
 */
class PresensiJamPelajaranTest extends TestCase
{
    use RefreshDatabase;

    private function bersihkan(Pesantren $pesantren): void
    {
        PresensiJamPelajaran::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->delete();
    }

    public function test_provision_tenant_mengisi_delapan_jam_bawaan(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->bersihkan($pesantren);

        app(ProvisionTenant::class)->jalankan($pesantren);

        $this->assertSame(8, PresensiJamPelajaran::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->count());
        $this->assertSame(count(PresensiDefault::jamPelajaran()), 8);
    }

    public function test_pengisian_idempoten_dan_tidak_menimpa_jam_yang_sudah_disunting(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->bersihkan($pesantren);

        PresensiDefault::untukPesantren($pesantren->id);

        PresensiJamPelajaran::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)
            ->where('jam_ke', 1)
            ->update(['jam_mulai' => '05:30:00', 'label' => 'Subuh']);

        // Dijalankan lagi — inilah yang terjadi tiap ProvisionTenant dipanggil ulang.
        PresensiDefault::untukPesantren($pesantren->id);

        $jam1 = PresensiJamPelajaran::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->where('jam_ke', 1)->first();

        $this->assertSame(8, PresensiJamPelajaran::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->count());
        $this->assertStringStartsWith('05:30', $jam1->jam_mulai);
        $this->assertSame('Subuh', $jam1->label);
    }

    public function test_aktif_untuk_menyembuhkan_pesantren_yang_belum_punya_jam(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->bersihkan($pesantren);

        $jam = PresensiJamPelajaran::aktifUntuk($pesantren->id);

        $this->assertCount(8, $jam);
        $this->assertSame(1, $jam->first()->jam_ke);
    }

    /**
     * Penyembuhan hanya untuk yang BENAR-BENAR kosong.
     *
     * Admin yang sengaja menonaktifkan seluruh jam tidak boleh dibanjiri delapan
     * jam bawaan lagi tiap halaman dibuka — itu akan membuat pengaturannya mustahil
     * dipertahankan.
     */
    public function test_aktif_untuk_tidak_mengisi_ulang_saat_semua_jam_dinonaktifkan(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->bersihkan($pesantren);

        PresensiDefault::untukPesantren($pesantren->id);
        PresensiJamPelajaran::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->update(['aktif' => false]);

        $jam = PresensiJamPelajaran::aktifUntuk($pesantren->id);

        $this->assertCount(0, $jam);
        $this->assertSame(8, PresensiJamPelajaran::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->count());
    }

    public function test_label_pilihan_menyertakan_rentang_waktu(): void
    {
        $jam = new PresensiJamPelajaran([
            'jam_ke' => 3,
            'jam_mulai' => '08:30:00',
            'jam_selesai' => '09:15:00',
        ]);

        $this->assertSame('Jam ke-3 (08:30–09:15)', $jam->labelPilihan());

        $jam->label = 'Istirahat';
        $this->assertSame('Jam ke-3 · Istirahat (08:30–09:15)', $jam->labelPilihan());
    }

    public function test_hanya_admin_pesantren_yang_bisa_membuka_master_jam(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        Livewire::actingAs($admin)->test(ListPresensiJamPelajarans::class)->assertSuccessful();

        $this->actingAs($ustadz);
        $this->assertFalse(PresensiJamPelajaranResource::canViewAny());
    }

    public function test_jam_ke_unik_per_pesantren_tapi_bebas_lintas_pesantren(): void
    {
        $a = Pesantren::factory()->create();
        $b = Pesantren::factory()->create();

        // Factory TIDAK melewati ProvisionTenant, jadi jamnya diisi eksplisit.
        PresensiDefault::untukPesantren($a->id);
        PresensiDefault::untukPesantren($b->id);

        // Dua pesantren boleh sama-sama punya "jam ke-1" — uniknya (pesantren_id, jam_ke).
        $this->assertSame(1, PresensiJamPelajaran::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $a->id)->where('jam_ke', 1)->count());
        $this->assertSame(1, PresensiJamPelajaran::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $b->id)->where('jam_ke', 1)->count());
    }
}
