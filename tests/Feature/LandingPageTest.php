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

    public function test_harga_paket_mengikuti_billing_setting_bukan_angka_mati(): void
    {
        $this->withoutVite();
        BillingSetting::set('harga_rintisan', 177_000);
        BillingSetting::set('harga_tumbuh', 288_000);

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertSee('Rp 177.000')
            ->assertSee('Rp 288.000');
    }

    /**
     * Kartu harga menawarkan dua siklus. Harga tahunan bukan angka terpisah yang
     * bisa menyimpang: ia turunan harga bulanan x DurasiLangganan::bulanBayar(),
     * jadi mengubah harga atau bonus di BillingSetting harus ikut menggeser
     * ketiga angkanya (coret, bayar, dan hemat) sekaligus.
     */
    public function test_pilihan_bulanan_dan_tahunan_beserta_bonusnya_tampil(): void
    {
        $this->withoutVite();
        BillingSetting::set('harga_tumbuh', 288_000);
        BillingSetting::set('bonus_bulan_tahunan', 2);

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertSee('Bulanan')
            ->assertSee('Tahunan')
            ->assertSee('2 bulan gratis')
            ->assertSee('Rp 288.000')          // harga paket per bulan, tetap ditulis
            ->assertSee('Rp 2.880.000')        // tahunan: bayar 10 bulan
            ->assertSee('Rp 3.456.000')        // harga coret: 12 x bulanan
            ->assertSee('Hemat Rp 576.000')    // 2 bulan yang digratiskan
            ->assertSee('Rp 1.152')            // tarif setara per santri/bulan (288.000 / 250)
            ->assertSee('Rp 11.520');          // tarif setara per santri/tahun (2.880.000 / 250)
    }

    /**
     * Angka yang ditonjolkan di kartu adalah tarif per santri, dan ia turunan dari
     * harga dibagi kuota — dua-duanya `BillingSetting`. Kuotanya digeser di sini
     * (bukan cuma harganya) karena pembagi inilah yang paling mudah diam-diam
     * dianggap tetap 250 saat menulis ulang salinan kartunya.
     */
    public function test_tarif_per_santri_ikut_kuota_bukan_pembagi_tetap(): void
    {
        $this->withoutVite();
        BillingSetting::set('harga_tumbuh', 288_000);
        BillingSetting::set('kuota_tumbuh', 200);
        BillingSetting::set('bonus_bulan_tahunan', 2);

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertSee('Rp 1.440')                             // 288.000 / 200
            ->assertSee('Rp 14.400')                            // 2.880.000 / 200
            ->assertSee('Setara pada kuota 200 santri')
            ->assertDontSee('Rp 1.152');                        // pembagi lama
    }

    /**
     * Bonus tahunan bisa dimatikan dari BillingSettingsPage. Kalau itu terjadi,
     * landing tidak boleh tetap memasang harga coret dan klaim bulan gratis yang
     * sudah tidak diberikan. Bonus 6 bulan di catatan kaki tidak ikut terpengaruh —
     * setelannya terpisah, karena itu asersinya menyasar angka nol, bukan frasanya.
     */
    public function test_klaim_bonus_tahunan_hilang_saat_bonus_dinolkan(): void
    {
        $this->withoutVite();
        BillingSetting::set('harga_tumbuh', 288_000);
        BillingSetting::set('bonus_bulan_enam', 1);
        BillingSetting::set('bonus_bulan_tahunan', 0);

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertSee('Rp 3.456.000')        // tahunan penuh: 12 bulan dibayar
            ->assertDontSee('0 bulan gratis')
            ->assertDontSee('Hemat Rp')
            ->assertSee('1 bulan gratis');     // catatan kaki durasi 6 bulan, tetap berlaku
    }

    /**
     * Di bawah lebar md, keenam tautan nav disembunyikan — dulu tanpa pengganti apa
     * pun, sehingga pengunjung HP tidak bisa mencapai seksi Harga maupun FAQ. Yang
     * dijaga di sini: panel penggantinya ada DAN isinya benar-benar tautan menu,
     * bukan cuma wadah kosong yang lolos assertSee.
     */
    public function test_menu_nav_punya_panel_untuk_layar_hp(): void
    {
        $this->withoutVite();

        $isi = $this->get($this->landingUrl())->assertOk()->getContent();

        $this->assertStringContainsString('id="menu-situs"', $isi);
        $this->assertStringContainsString('for="menu-situs"', $isi);

        [$bar, $panel] = explode('id="menu-situs-panel"', $isi, 2);

        $this->assertStringContainsString('#harga', $panel, 'Panel menu HP tidak memuat tautan seksi.');
        $this->assertStringContainsString('#faq', $panel);
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
     * Dua janji yang saling menggantikan di catatan kaki #harga. "Tanpa kontrak
     * jangka panjang" dicabut atas keputusan pemilik produk, dan sebagai gantinya
     * halaman ini menyatakan harga bisa berubah — jadi angka di kartu tidak terbaca
     * sebagai tarif yang terkunci selamanya bagi calon pelanggan yang baru membaca.
     */
    public function test_catatan_harga_menyebut_bisa_berubah_tanpa_janji_bebas_kontrak(): void
    {
        $this->withoutVite();

        $this->get($this->landingUrl())
            ->assertOk()
            ->assertSee('Harga dapat berubah sewaktu-waktu')
            ->assertDontSee('tanpa kontrak jangka panjang')
            ->assertDontSee('kontrak');
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
