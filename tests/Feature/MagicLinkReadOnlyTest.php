<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\TagihanSpp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MagicLinkReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function santriDenganWali(): Santri
    {
        $pesantren = Pesantren::factory()->create();
        $wali      = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);

        return Santri::factory()->create([
            'pesantren_id'   => $pesantren->id,
            'wali_santri_id' => $wali->id,
            'status_aktif'   => true,
        ]);
    }

    public function test_magic_link_bisa_lihat_halaman_laporan(): void
    {
        $santri = $this->santriDenganWali();

        $this->get("/report/{$santri->uuid}")
            ->assertOk()
            // Halaman report harus menautkan ke inventaris — satu-satunya pintu
            // masuk pemegang magic link ke halaman inventaris yang di-whitelist.
            ->assertSee(route('wali.santri.inventaris', $santri->id));
    }

    public function test_sesi_magic_link_tidak_bisa_buka_dashboard_wali(): void
    {
        $santri = $this->santriDenganWali();

        // Masuk lewat magic link → set flag magic_link_session di sesi.
        $this->get("/report/{$santri->uuid}")->assertOk();

        // Halaman portal agregat harus dialihkan kembali ke halaman laporan,
        // bukan menampilkan dashboard penuh.
        $this->get('/wali/dashboard')
            ->assertRedirect(route('wali.magic.report', $santri->uuid));
    }

    public function test_sesi_magic_link_bisa_buka_statistik_dan_detail_santrinya(): void
    {
        $santri = $this->santriDenganWali();

        $this->get("/report/{$santri->uuid}")->assertOk();

        // Halaman yang render penuh di SQLite: dibuktikan tampil (200).
        $this->get("/wali/santri/{$santri->id}")->assertOk();
        $this->get("/wali/santri/{$santri->id}/inventaris")->assertOk();

        // Halaman statistik memakai SQL khusus Postgres (TO_CHAR) yang tak jalan
        // di SQLite test, jadi cukup buktikan MIDDLEWARE meloloskannya —
        // tidak dialihkan balik ke report seperti halaman portal agregat.
        $reportUrl = route('wali.magic.report', $santri->uuid);
        foreach (['tahfidz', 'kesehatan', 'mutabaah'] as $bagian) {
            $lokasi = $this->get("/wali/santri/{$santri->id}/{$bagian}")
                ->headers->get('Location');
            $this->assertNotSame($reportUrl, $lokasi, "Route {$bagian} tidak boleh dialihkan ke report");
        }
    }

    public function test_sesi_magic_link_tidak_bisa_lihat_statistik_santri_lain(): void
    {
        $santri = $this->santriDenganWali();
        // Santri lain (wali/pesantren berbeda) — id ditebak lewat URL.
        $santriLain = $this->santriDenganWali();

        $this->get("/report/{$santri->uuid}")->assertOk();

        // Route detail diizinkan, tapi santri di luar tautan ini dialihkan
        // ke report yang benar — bukan membocorkan data santri lain.
        $this->get("/wali/santri/{$santriLain->id}/tahfidz")
            ->assertRedirect(route('wali.magic.report', $santri->uuid));
    }

    public function test_sesi_magic_link_tidak_bisa_kirim_post_konfirmasi_spp(): void
    {
        $santri = $this->santriDenganWali();

        // Tagihan valid milik santri agar route-model-binding sukses — sehingga
        // yang menolak POST benar-benar middleware magic.block (403), bukan 404 binding.
        $tagihan = TagihanSpp::create([
            'pesantren_id' => $santri->pesantren_id,
            'santri_id'    => $santri->id,
            'bulan'        => 1,
            'tahun'        => 2026,
            'nominal'      => 100000,
            'status'       => 'belum_bayar',
        ]);

        $this->get("/report/{$santri->uuid}")->assertOk();

        // Route POST wali.* harus ditolak untuk sesi magic link.
        $this->post("/wali/spp/{$tagihan->id}/konfirmasi")
            ->assertForbidden();

        // Pastikan tidak ada mutasi yang terjadi.
        $this->assertSame('belum_bayar', $tagihan->fresh()->status->value);
    }

    public function test_wali_login_normal_tetap_bisa_buka_dashboard(): void
    {
        $santri = $this->santriDenganWali();
        $wali   = $santri->wali;

        // Login normal (tanpa flag magic_link_session) tidak boleh terpengaruh.
        $this->actingAs($wali)
            ->get('/wali/dashboard')
            ->assertOk();
    }
}
