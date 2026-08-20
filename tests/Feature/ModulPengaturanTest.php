<?php

namespace Tests\Feature;

use App\Enums\Modul;
use App\Models\ModulPengaturan;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\ProvisionTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tiga lapis pengisi baris pengaturan diuji di sini. Ketiganya disengaja: modul
 * Mutaba'ah pernah lumpuh diam-diam berbulan-bulan karena satu-satunya pengisi
 * datanya adalah migrasi yang hanya jalan sekali (PRD §22, kelas bug v4.21).
 *
 * Satu tes di berkas ini memikul beban yang lebih besar dari yang lain:
 * test_default_semua_modul_menyala adalah kontrak "pesantren yang sudah berjalan
 * tidak berubah sama sekali". Fitur ini opt-OUT; begitu satu default terbalik jadi
 * false, ratusan pesantren kehilangan menu tanpa ada yang meminta.
 */
class ModulPengaturanTest extends TestCase
{
    use RefreshDatabase;

    public function test_provision_tenant_membuat_baris_pengaturan(): void
    {
        $pesantren = Pesantren::factory()->create();

        // Factory tidak melewati ProvisionTenant, jadi barisnya memang belum ada.
        ModulPengaturan::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->delete();

        app(ProvisionTenant::class)->jalankan($pesantren);

        $this->assertDatabaseHas('modul_pengaturan', [
            'pesantren_id' => $pesantren->id,
            'presensi_aktif' => true,
        ]);
    }

    public function test_provision_tenant_idempoten(): void
    {
        $pesantren = Pesantren::factory()->create();

        app(ProvisionTenant::class)->jalankan($pesantren);
        app(ProvisionTenant::class)->jalankan($pesantren);

        $this->assertSame(1, ModulPengaturan::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->count());
    }

    public function test_untuk_menyembuhkan_tenant_yang_barisnya_hilang(): void
    {
        $pesantren = Pesantren::factory()->create();

        ModulPengaturan::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)->delete();

        $pengaturan = ModulPengaturan::untuk($pesantren->id);

        $this->assertSame($pesantren->id, $pengaturan->pesantren_id);
        // refresh() setelah firstOrCreate: default hidup di DB, bukan di model.
        // Tanpa itu seluruh kolom kembali null dan SEMUA modul terbaca mati.
        $this->assertTrue($pengaturan->presensi_aktif);
    }

    /** Kontrak "tenant lama nol perubahan" — keenamnya, satu per satu. */
    public function test_default_semua_modul_menyala(): void
    {
        $pesantren = Pesantren::factory()->create();

        $pengaturan = ModulPengaturan::untuk($pesantren->id);

        foreach (Modul::cases() as $modul) {
            $this->assertTrue(
                (bool) $pengaturan->{$modul->kolom()},
                "Modul {$modul->value} harus menyala secara bawaan — fitur ini opt-out."
            );
        }
    }

    public function test_untuk_hanya_sekali_query_dalam_satu_request(): void
    {
        $pesantren = Pesantren::factory()->create();
        ModulPengaturan::untuk($pesantren->id);

        DB::enableQueryLog();
        DB::flushQueryLog();

        // Sidebar memanggil ini ±27 kali per render. Tanpa memo, itu 27 query.
        ModulPengaturan::untuk($pesantren->id);
        ModulPengaturan::untuk($pesantren->id);
        ModulPengaturan::untuk($pesantren->id);

        $this->assertCount(0, DB::getQueryLog());

        DB::disableQueryLog();
    }

    public function test_memo_dibuang_setelah_baris_disimpan(): void
    {
        $pesantren = Pesantren::factory()->create();

        $this->assertTrue(ModulPengaturan::untuk($pesantren->id)->keuangan_aktif);

        ModulPengaturan::untuk($pesantren->id)->update(['keuangan_aktif' => false]);

        // Memo yang tidak dibuang akan membuat halaman yang dirender SETELAH save
        // tetap memperlihatkan menu yang baru saja dimatikan admin.
        $this->assertFalse(ModulPengaturan::untuk($pesantren->id)->keuangan_aktif);
    }

    /**
     * Tanpa konteks pesantren, modul selalu dianggap menyala. Ini yang menjaga
     * super_admin (pesantren_id null) tidak pernah kehilangan menu apa pun.
     */
    public function test_modul_menyala_saat_tidak_ada_konteks_pesantren(): void
    {
        $pesantren = Pesantren::factory()->create();
        ModulPengaturan::untuk($pesantren->id)->update([
            'keuangan_aktif' => false,
            'presensi_aktif' => false,
        ]);

        $this->actingAs(User::factory()->superAdmin()->create());

        $this->assertTrue(Modul::Keuangan->aktif());
        $this->assertTrue(Modul::Presensi->aktif());
    }

    public function test_aktif_membaca_pesantren_yang_dioper_bukan_pengguna_login(): void
    {
        $pesantrenA = Pesantren::factory()->create();
        $pesantrenB = Pesantren::factory()->create();

        ModulPengaturan::untuk($pesantrenA->id)->update(['tahfidz_aktif' => false]);

        // Jalur magic link tidak punya tenant.resolve, jadi pemanggilnya WAJIB
        // bisa mengoper pesantren_id milik santri secara eksplisit.
        $this->assertFalse(Modul::Tahfidz->aktif($pesantrenA->id));
        $this->assertTrue(Modul::Tahfidz->aktif($pesantrenB->id));
    }
}
