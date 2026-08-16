<?php

namespace Tests\Feature;

use App\Models\MasterPengumuman;
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
        // §1.8 Fase 1: permukaan wali hidup di host pesantren, jadi seluruh
        // route('wali.*') di berkas ini harus menunjuk ke sana.
        $this->pakaiHostTenant($pesantren);
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);

        return Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'wali_santri_id' => $wali->id,
            'status_aktif' => true,
        ]);
    }

    public function test_magic_link_bisa_lihat_halaman_laporan(): void
    {
        $santri = $this->santriDenganWali();

        $this->get(route('wali.magic.report', $santri->uuid))
            ->assertOk()
            // Halaman report harus menautkan ke inventaris — satu-satunya pintu
            // masuk pemegang magic link ke halaman inventaris yang di-whitelist.
            ->assertSee(route('wali.santri.inventaris', $santri->id));
    }

    public function test_magic_link_melihat_pengumuman_di_halaman_report(): void
    {
        $santri = $this->santriDenganWali();

        // Pengumuman disurutkan ke report karena sesi magic link tak punya
        // akses dashboard/nav tempat pengumuman biasanya muncul.
        MasterPengumuman::create([
            'pesantren_id' => $santri->pesantren_id,
            'judul_maklumat' => 'Libur Idul Adha PENGUMUMAN_MAGIC',
            'isi_maklumat' => 'Kegiatan diliburkan.',
            'target_audience' => 'wali',
        ]);

        $this->get(route('wali.magic.report', $santri->uuid))
            ->assertOk()
            ->assertSee('Libur Idul Adha PENGUMUMAN_MAGIC');
    }

    public function test_sesi_magic_link_tidak_bisa_buka_dashboard_wali(): void
    {
        $santri = $this->santriDenganWali();

        // Masuk lewat magic link → set flag magic_link_session di sesi.
        $this->get(route('wali.magic.report', $santri->uuid))->assertOk();

        // Halaman portal agregat harus dialihkan kembali ke halaman laporan,
        // bukan menampilkan dashboard penuh.
        $this->get(route('wali.dashboard'))
            ->assertRedirect(route('wali.magic.report', $santri->uuid));
    }

    public function test_sesi_magic_link_bisa_buka_statistik_dan_detail_santrinya(): void
    {
        $santri = $this->santriDenganWali();

        $this->get(route('wali.magic.report', $santri->uuid))->assertOk();

        // Halaman yang render penuh di SQLite: dibuktikan tampil (200).
        $this->get(route('wali.santri.show', $santri->id))->assertOk();
        $this->get(route('wali.santri.inventaris', $santri->id))->assertOk();

        // Halaman statistik memakai SQL khusus Postgres (TO_CHAR) yang tak jalan
        // di SQLite test, jadi cukup buktikan MIDDLEWARE meloloskannya —
        // tidak dialihkan balik ke report seperti halaman portal agregat.
        $reportUrl = route('wali.magic.report', $santri->uuid);
        foreach (['tahfidz', 'kesehatan', 'mutabaah'] as $bagian) {
            $lokasi = $this->get(route("wali.santri.{$bagian}", $santri->id))
                ->headers->get('Location');
            $this->assertNotSame($reportUrl, $lokasi, "Route {$bagian} tidak boleh dialihkan ke report");
        }
    }

    public function test_sesi_magic_link_tidak_bisa_lihat_statistik_santri_lain(): void
    {
        $santri = $this->santriDenganWali();
        // Santri lain (wali/pesantren berbeda) — id ditebak lewat URL.
        $santriLain = $this->santriDenganWali();

        // santriDenganWali() membuat pesantren baru tiap panggilan, jadi konteks
        // host harus dikembalikan ke pesantren santri PERTAMA — di situlah sesi
        // magic link-nya hidup (§1.8 Fase 1: sesi ber-scope host).
        $this->pakaiHostTenant($santri->pesantren);

        $this->get(route('wali.magic.report', $santri->uuid))->assertOk();

        // Route detail diizinkan, tapi santri di luar tautan ini dialihkan
        // ke report yang benar — bukan membocorkan data santri lain.
        $this->get(route('wali.santri.tahfidz', $santriLain->id))
            ->assertRedirect(route('wali.magic.report', $santri->uuid));
    }

    public function test_sesi_magic_link_tidak_bisa_kirim_post_konfirmasi_spp(): void
    {
        $santri = $this->santriDenganWali();

        // Tagihan valid milik santri agar route-model-binding sukses — sehingga
        // yang menolak POST benar-benar middleware magic.block (403), bukan 404 binding.
        $tagihan = TagihanSpp::create([
            'pesantren_id' => $santri->pesantren_id,
            'santri_id' => $santri->id,
            'bulan' => 1,
            'tahun' => 2026,
            'nominal' => 100000,
            'status' => 'belum_bayar',
        ]);

        $this->get(route('wali.magic.report', $santri->uuid))->assertOk();

        // Route POST wali.* harus ditolak untuk sesi magic link.
        $this->post(route('wali.spp.konfirmasi', $tagihan->id))
            ->assertForbidden();

        // Pastikan tidak ada mutasi yang terjadi.
        $this->assertSame('belum_bayar', $tagihan->fresh()->status->value);
    }

    /**
     * session()->regenerate() saat login hanya mengganti ID sesi — ISINYA
     * dipertahankan. Tanpa pembersihan eksplisit, bendera magic_link_session
     * yang tertinggal (mis. sisa mencoba sandbox publik di /coba) bertahan
     * melewati login dan mengunci wali ke mode laporan baca-saja.
     */
    public function test_bendera_magic_link_tidak_bertahan_setelah_login_normal(): void
    {
        $santri = $this->santriDenganWali();
        $wali = $santri->wali;
        $wali->forceFill(['password' => bcrypt('RahasiaKuat123')])->save();

        // Kunjungi magic link dulu — persis alur calon pelanggan yang mencoba demo.
        $this->get(route('wali.magic.report', $santri->uuid))->assertOk();
        $this->assertTrue(session('magic_link_session'));

        $this->post('/login', [
            'email' => $wali->email,
            'password' => 'RahasiaKuat123',
        ]);

        $this->assertNull(session('magic_link_session'));
        $this->assertNull(session('magic_link_santri_id'));

        $this->get(route('wali.dashboard'))->assertOk();
    }

    public function test_wali_login_normal_tetap_bisa_buka_dashboard(): void
    {
        $santri = $this->santriDenganWali();
        $wali = $santri->wali;

        // Login normal (tanpa flag magic_link_session) tidak boleh terpengaruh.
        $this->actingAs($wali)
            ->get(route('wali.dashboard'))
            ->assertOk();
    }
}
