<?php

namespace Tests\Feature;

use App\Models\BillingSetting;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private function landingUrl(): string
    {
        return 'http://'.config('app.base_domain').'/';
    }

    public function test_tombol_daftar_tampil_di_header_saat_registrasi_dibuka(): void
    {
        $this->withoutVite();
        PlatformSetting::set('registration_open', true);

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertSee('id="nav-daftar"', false);
    }

    public function test_tombol_daftar_disembunyikan_di_header_saat_registrasi_ditutup(): void
    {
        $this->withoutVite();
        PlatformSetting::set('registration_open', false);

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertDontSee('id="nav-daftar"', false);
    }

    /**
     * Bukan hanya tombol header: hero, kartu harga, CTA penutup, dan footer dulu
     * tetap menawarkan pintu yang sedang dikunci.
     */
    public function test_tidak_ada_satu_pun_tautan_daftar_saat_registrasi_ditutup(): void
    {
        $this->withoutVite();
        PlatformSetting::set('registration_open', false);

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertDontSee(route('register'), false);
    }

    /**
     * Seluruh paket pindah ke /harga (lihat HargaPageTest) — landing tidak lagi
     * memajang satu pun. Yang dijaga di sini jalan keluarnya: nav & footer tetap
     * menautkan halaman itu, dan kartu paketnya benar-benar tidak tertinggal
     * separuh (nama paket tanpa angka justru lebih membingungkan daripada tidak
     * ada sama sekali).
     */
    public function test_landing_menautkan_halaman_harga_tanpa_memajang_paketnya(): void
    {
        $this->withoutVite();

        $isi = $this->get($this->landingUrl())->assertOk()->getContent();

        $this->assertStringContainsString(route('harga'), $isi);
        $this->assertStringNotContainsString('Paling Populer', $isi);
        $this->assertStringNotContainsString('Setara pada kuota', $isi);
        $this->assertStringNotContainsString('id="harga"', $isi);
    }

    /**
     * Satu-satunya angka harga yang tersisa di landing ada di jawaban FAQ "Berapa
     * biaya Walisantri?". Dulu ia angka mati (Rp 150.000) yang menyimpang diam-diam
     * begitu harga digeser di BillingSettingsPage; kini turunan BillingSetting.
     */
    public function test_harga_terendah_di_faq_ikut_billing_setting(): void
    {
        $this->withoutVite();
        BillingSetting::set('harga_rintisan', 177_000);

        $isi = $this->get($this->landingUrl())->assertOk()->getContent();

        $this->assertStringNotContainsString('Rp 150.000', $isi);
        $this->assertStringContainsString('Rp 177.000', $isi);
    }

    /**
     * Toggle siklus ikut pindah ke /harga. Kalau selectornya tertinggal di sini ia
     * jadi CSS mati yang menunggu seseorang menyalin markupnya kembali.
     */
    public function test_selector_siklus_harga_tidak_tertinggal_di_landing(): void
    {
        $this->withoutVite();

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertDontSee('siklus-bulanan', false)
            ->assertDontSee('siklus-tahunan', false);
    }

    /**
     * Di bawah lebar md, seluruh tautan nav disembunyikan — dulu tanpa pengganti apa
     * pun, sehingga pengunjung HP tidak bisa mencapai halaman Harga maupun seksi FAQ.
     * Yang dijaga di sini: panel penggantinya ada DAN isinya benar-benar tautan menu,
     * bukan cuma wadah kosong yang lolos assertSee.
     */
    public function test_menu_nav_punya_panel_untuk_layar_hp(): void
    {
        $this->withoutVite();

        $isi = $this->get($this->landingUrl())->assertOk()->getContent();

        $this->assertStringContainsString('id="menu-situs"', $isi);
        $this->assertStringContainsString('for="menu-situs"', $isi);

        [$bar, $panel] = explode('id="menu-situs-panel"', $isi, 2);

        $this->assertStringContainsString(route('harga'), $panel, 'Panel menu HP tidak memuat tautan Harga.');
        $this->assertStringContainsString('#faq', $panel, 'Panel menu HP tidak memuat tautan seksi.');
        $this->assertStringContainsString(route('login'), $panel, 'Masuk dipindah ke panel, jadi ia wajib ada di sana.');

        // Tombol mode gelap justru TIDAK boleh mengungsi ke panel: ia tetap di bar,
        // terjangkau satu ketukan di layar HP sekalipun.
        $this->assertStringContainsString('data-tema-tombol', $bar);
        $this->assertStringNotContainsString('data-tema-tombol', $panel);
    }

    /**
     * Mode gelap dipasang sebagai kelas di <html> oleh skrip di <head>, bukan
     * lewat prefers-color-scheme saja — pembaca boleh menimpa setelan
     * perangkatnya dan pilihan itu diingat. Yang dijaga di sini bagian yang bisa
     * diam-diam hilang saat menyunting <head>: skripnya harus ikut ter-render
     * (kalau tidak, pembaca bermode gelap kena kedip putih tiap membuka halaman)
     * dan tombolnya harus ada di nav.
     */
    public function test_pemilih_mode_gelap_tersedia_di_halaman_publik(): void
    {
        $this->withoutVite();

        foreach ([$this->landingUrl(), 'http://'.config('app.domain').'/panduan'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('data-tema-tombol', false)
                ->assertSee('localStorage.getItem(KUNCI)', false)
                ->assertSee('Ganti mode terang/gelap');
        }
    }

    /**
     * Landing tidak lagi menjual masa trial: tombol dan seluruh salinannya bicara
     * pendaftaran, bukan "coba gratis N hari". Tes ini menggantikan
     * test_lama_trial_mengikuti_billing_setting — dulu ia menjaga agar lama trial
     * tidak di-hardcode di Blade; sekarang yang perlu dijaga adalah janji itu tidak
     * muncul kembali diam-diam, berapa pun nilai trial_days di BillingSetting.
     */
    public function test_landing_tidak_menjanjikan_trial(): void
    {
        $this->withoutVite();
        PlatformSetting::set('registration_open', true);
        BillingSetting::set('trial_days', 21);

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertSee('Daftar Sekarang')
            ->assertDontSee('21 hari')
            ->assertDontSee('Trial')
            ->assertDontSee('trial');
    }

    public function test_modul_presensi_dipromosikan_di_landing(): void
    {
        $this->withoutVite();

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertSee('Presensi')
            ->assertSee('Kartu QR');
    }

    /**
     * Empat klaim ini pernah tertulis di landing padahal tidak benar. Dikunci di
     * sini supaya tidak diam-diam kembali lewat penyuntingan copy berikutnya:
     * billing sudah hidup (bukan beta gratis), tenancy-nya single-DB dengan
     * scoping per baris (bukan database terisolasi), tidak ada satu pun
     * notifikasi yang menyasar wali, dan pendaftaran bersifat self-serve.
     */
    public function test_klaim_yang_sudah_dicabut_tidak_muncul_lagi(): void
    {
        $this->withoutVite();

        $response = $this->get($this->landingUrl())->assertOk();

        foreach ([
            'fase beta testing',
            'database yang terisolasi',
            'Notifikasi ke wali',
            '1-2 hari kerja',
        ] as $klaim) {
            $response->assertDontSee($klaim, false);
        }
    }

    public function test_tautan_demo_hilang_dari_landing_saat_demo_ditutup(): void
    {
        $this->withoutVite();
        PlatformSetting::set('demo_open', false);

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertDontSee(route('demo'), false);
    }

    /**
     * Kedua pintu masuk tertutup: landing tidak boleh menawarkan ajakan apa pun,
     * cukup memberi tahu keadaannya. Tombol "Masuk" tetap ada — pesantren yang
     * sudah terdaftar tidak ikut terkunci.
     */
    public function test_kedua_pintu_tertutup_menampilkan_pesan_tanpa_cta(): void
    {
        $this->withoutVite();
        PlatformSetting::set('registration_open', false);
        PlatformSetting::set('demo_open', false);

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertDontSee(route('register'), false)
            ->assertDontSee(route('demo'), false)
            ->assertSee('sedang ditutup sementara')
            ->assertSee(route('login'), false);
    }
}
