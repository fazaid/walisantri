<?php

namespace Tests\Feature;

use App\Enums\Modul;
use App\Models\Kelas;
use App\Models\ModulPengaturan;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\TahfidzProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modul yang dimatikan admin juga lenyap dari portal wali santri.
 *
 * Dua hal yang khas di permukaan ini, dan keduanya berbeda dari panel admin:
 *
 * 1. Rutenya menjawab 404, BUKAN 403. Bagi orang tua, modul yang pesantrennya tidak
 *    pakai memang tidak ada; 403 mengumumkan keberadaan sesuatu yang sedang ditolak
 *    dan mengundang pertanyaan ke pengasuh tentang fitur yang bukan haknya.
 * 2. ⚠️ Jalur magic link (wali.magic.report) TIDAK memakai middleware tenant.resolve,
 *    jadi konteks tenant bisa kosong di sana. Pemeriksaan modul di jalur itu wajib
 *    membaca $santri->pesantren_id. Salahnya tidak memunculkan galat apa pun —
 *    wali cuma melihat seksi milik pesantren lain.
 */
class WaliModulTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    private User $wali;

    private Santri $santri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        // §1.8 Fase 1: permukaan wali hidup di host pesantren.
        $this->pakaiHostTenant($this->pesantren);

        $this->wali = User::factory()->waliSantri()->create(['pesantren_id' => $this->pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $this->pesantren->id]);
        $this->santri = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'wali_santri_id' => $this->wali->id,
            'kelas_id' => $kelas->id,
            'status_aktif' => true,
        ]);
    }

    private function matikan(Modul ...$modul): void
    {
        ModulPengaturan::untuk($this->pesantren->id)->update(
            collect($modul)->mapWithKeys(fn (Modul $m) => [$m->kolom() => false])->all()
        );
    }

    public function test_rute_modul_yang_dimatikan_menjawab_404(): void
    {
        $this->matikan(Modul::Keuangan, Modul::Rapor, Modul::Presensi, Modul::Tahfidz, Modul::Kesantrian);

        $this->actingAs($this->wali);

        foreach ([
            route('wali.spp'),
            route('wali.uang-saku'),
            route('wali.rapor'),
            route('wali.izin'),
            route('wali.santri.presensi', $this->santri),
            route('wali.santri.tahfidz', $this->santri),
            route('wali.santri.kesehatan', $this->santri),
            route('wali.santri.mutabaah', $this->santri),
            route('wali.santri.inventaris', $this->santri),
        ] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    /** Ketiganya inti portal — tidak dimiliki modul mana pun. */
    public function test_dashboard_pengumuman_dan_detail_santri_tidak_pernah_ikut_mati(): void
    {
        $this->matikan(...Modul::cases());

        $this->actingAs($this->wali);

        $this->get(route('wali.dashboard'))->assertOk();
        $this->get(route('wali.pengumuman'))->assertOk();
        $this->get(route('wali.santri.show', $this->santri))->assertOk();
    }

    /** POST tidak bisa dijaga lapis view — itu sebabnya penjaganya middleware rute. */
    public function test_post_konfirmasi_spp_ikut_diblokir(): void
    {
        $this->matikan(Modul::Keuangan);

        $this->actingAs($this->wali)
            ->post(route('wali.spp.konfirmasi', 1))
            ->assertNotFound();
    }

    public function test_tab_bottom_nav_mengikuti_modul(): void
    {
        $this->actingAs($this->wali);

        $this->get(route('wali.dashboard'))->assertOk()
            ->assertSee('Uang Saku')
            ->assertSee('Rapor');

        $this->matikan(Modul::Keuangan, Modul::Rapor);

        $respons = $this->get(route('wali.dashboard'))->assertOk();
        $respons->assertDontSee(route('wali.spp'), escape: false);
        $respons->assertDontSee(route('wali.uang-saku'), escape: false);
        $respons->assertDontSee(route('wali.rapor'), escape: false);
        // Bar tidak pernah kosong: Beranda & Pengumuman tanpa syarat.
        $respons->assertSee(route('wali.pengumuman'), escape: false);
    }

    public function test_seksi_detail_santri_lenyap_mengikuti_modul(): void
    {
        TahfidzProgress::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $this->santri->id,
            'ustadz_id' => User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id])->id,
            'tanggal' => now()->toDateString(),
            'tipe_setoran' => 'Sabaq',
            'nama_surah' => 'Al-Baqarah',
            'halaman_mulai' => 1,
            'halaman_selesai' => 3,
            'nilai_kelancaran' => 'Mumtaz',
        ]);

        $this->actingAs($this->wali);

        $this->get(route('wali.santri.show', $this->santri))->assertOk()
            ->assertSee('Capaian Hafalan');

        $this->matikan(Modul::Tahfidz, Modul::Kesantrian, Modul::Presensi);

        $this->get(route('wali.santri.show', $this->santri))->assertOk()
            ->assertDontSee('Capaian Hafalan')
            ->assertDontSee('Riwayat Setoran')
            ->assertDontSee('Status Kesehatan');
    }

    /**
     * Jalur magic link tanpa tenant.resolve. Kalau pemeriksaan modul di
     * SantriDetailPresenter mengambil konteks tenant alih-alih $santri->pesantren_id,
     * tes ini merah — dan di produksi gejalanya tidak akan berupa galat.
     */
    public function test_jalur_magic_link_membaca_pesantren_santri_bukan_konteks_tenant(): void
    {
        $this->matikan(Modul::Tahfidz);

        $this->get(route('wali.magic.report', $this->santri->uuid))
            ->assertOk()
            ->assertDontSee('Capaian Hafalan');
    }

    public function test_halaman_rapor_wali_jatuh_ke_tab_aktif_pertama(): void
    {
        // Tahfidz mati → default lama ('tahfidz') membuka tab yang tombolnya tidak ada.
        $this->matikan(Modul::Tahfidz);

        $this->actingAs($this->wali)
            ->get(route('wali.rapor'))
            ->assertOk()
            ->assertDontSee('📖 Tahfidz')
            ->assertSee('🌱 Karakter');
    }

    public function test_rapor_wali_menjelaskan_diri_saat_seluruh_modulnya_mati(): void
    {
        $this->matikan(Modul::Tahfidz, Modul::Kesantrian, Modul::Akademik);

        $this->actingAs($this->wali)
            ->get(route('wali.rapor'))
            ->assertOk()
            ->assertSee('belum mengaktifkan satu pun modul rapor');
    }
}
