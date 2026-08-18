<?php

namespace Tests\Feature;

use App\Models\BillingSetting;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /harga adalah rumah kanonik paket harga sejak §1.6 — sebagian besar kasus di
 * berkas ini pindahan dari LandingPageTest, mengikuti kartu yang pindah halaman.
 */
class HargaPageTest extends TestCase
{
    use RefreshDatabase;

    private function hargaUrl(): string
    {
        return 'http://'.config('app.base_domain').'/harga';
    }

    public function test_harga_paket_mengikuti_billing_setting_bukan_angka_mati(): void
    {
        $this->withoutVite();
        BillingSetting::set('harga_rintisan', 177_000);
        BillingSetting::set('harga_tumbuh', 288_000);

        $this->get($this->hargaUrl())
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

        $this->get($this->hargaUrl())
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
     * Togglenya wajib tetap bebas JavaScript: dua radio sr-only sebagai saudara
     * langsung isi seksi, disembunyikan CSS di <head>. Kalau markup-nya dibungkus
     * ulang, selector siblingnya putus dan salah satu siklus tidak pernah tampil —
     * gejala yang tidak kelihatan dari assertSee angka mana pun.
     */
    public function test_toggle_siklus_tetap_tanpa_javascript(): void
    {
        $this->withoutVite();

        $this->get($this->hargaUrl())
            ->assertOk()
            ->assertSee('id="siklus-bulanan"', false)
            ->assertSee('id="siklus-tahunan"', false)
            ->assertSee('#siklus-tahunan:checked ~ * .harga-bulanan', false);
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

        $this->get($this->hargaUrl())
            ->assertOk()
            ->assertSee('Rp 1.440')                             // 288.000 / 200
            ->assertSee('Rp 14.400')                            // 2.880.000 / 200
            ->assertSee('Setara pada kuota 200 santri')
            ->assertDontSee('Rp 1.152');                        // pembagi lama
    }

    /**
     * Bonus tahunan bisa dimatikan dari BillingSettingsPage. Kalau itu terjadi,
     * halaman ini tidak boleh tetap memasang harga coret dan klaim bulan gratis
     * yang sudah tidak diberikan. Bonus 6 bulan di catatan kaki tidak ikut
     * terpengaruh — setelannya terpisah, karena itu asersinya menyasar angka nol,
     * bukan frasanya.
     */
    public function test_klaim_bonus_tahunan_hilang_saat_bonus_dinolkan(): void
    {
        $this->withoutVite();
        BillingSetting::set('harga_tumbuh', 288_000);
        BillingSetting::set('bonus_bulan_enam', 1);
        BillingSetting::set('bonus_bulan_tahunan', 0);

        $this->get($this->hargaUrl())
            ->assertOk()
            ->assertSee('Rp 3.456.000')        // tahunan penuh: 12 bulan dibayar
            ->assertDontSee('0 bulan gratis')
            ->assertDontSee('Hemat Rp')
            ->assertSee('1 bulan gratis');     // catatan kaki durasi 6 bulan, tetap berlaku
    }

    /**
     * Add-on kuota Maju punya dua angka yang mudah diam-diam ditulis tangan:
     * tarif per 100 santri dan contoh perhitungannya. Keduanya digeser dari
     * BillingSetting di sini supaya contoh yang salah ikut ketahuan — bukan cuma
     * tarifnya.
     */
    public function test_add_on_maju_mengikuti_billing_setting_termasuk_contohnya(): void
    {
        $this->withoutVite();
        BillingSetting::set('harga_maju_base', 800_000);
        BillingSetting::set('harga_maju_per_100_santri', 123_000);
        BillingSetting::set('kuota_maju_base', 1000);

        $this->get($this->hargaUrl())
            ->assertOk()
            ->assertSee('Rp 123.000')
            ->assertSee('1.200 santri')        // contoh: base + 200
            ->assertSee('Rp 1.046.000');       // 800.000 + (2 x 123.000)
    }

    /**
     * Kelima Gate dihapus di v4.20: semua modul terbuka di semua paket, dan yang
     * membedakan hanya kuota. Tabel perbandingan karena itu tidak boleh punya satu
     * pun sel "tidak termasuk" — begitu ada, ia menjanjikan penguncian yang tidak
     * ditegakkan kode mana pun.
     */
    public function test_tabel_perbandingan_tidak_mencoret_modul_di_paket_mana_pun(): void
    {
        $this->withoutVite();

        $isi = $this->get($this->hargaUrl())->assertOk()->getContent();

        $tabel = substr($isi, strpos($isi, '<table'), strpos($isi, '</table>') - strpos($isi, '<table'));

        $this->assertStringContainsString('Kuota santri', $tabel);
        $this->assertStringNotContainsString('✕', $tabel);
        $this->assertStringNotContainsString('&mdash;', $tabel);
        $this->assertStringNotContainsString('Tidak termasuk', $tabel);
    }

    /**
     * Dua janji yang saling menggantikan di catatan kaki harga. "Tanpa kontrak
     * jangka panjang" dicabut atas keputusan pemilik produk, dan sebagai gantinya
     * halaman ini menyatakan harga bisa berubah — jadi angka di kartu tidak terbaca
     * sebagai tarif yang terkunci selamanya bagi calon pelanggan yang baru membaca.
     */
    public function test_catatan_harga_menyebut_bisa_berubah_tanpa_janji_bebas_kontrak(): void
    {
        $this->withoutVite();

        $this->get($this->hargaUrl())
            ->assertOk()
            ->assertSee('Harga dapat berubah sewaktu-waktu')
            ->assertDontSee('tanpa kontrak jangka panjang')
            ->assertDontSee('kontrak');
    }

    /**
     * Trial tetap berjalan sebagai mekanik, tapi tidak lagi dipasarkan (v4.45).
     * Halaman harga adalah tempat paling mudah janji itu menyelinap kembali.
     */
    public function test_halaman_harga_tidak_menjanjikan_trial(): void
    {
        $this->withoutVite();
        PlatformSetting::set('registration_open', true);
        BillingSetting::set('trial_days', 21);

        $this->get($this->hargaUrl())
            ->assertOk()
            ->assertSee('Daftar Sekarang')
            ->assertDontSee('21 hari')
            ->assertDontSee('Trial')
            ->assertDontSee('trial');
    }

    /**
     * Gerbangnya sama persis dengan landing & /panduan. Lubang yang dijaga di sini
     * pernah nyata di /panduan: halaman yang tidak mengirim $registrationOpen ke
     * partial nav menyisakan pintu yang masih terbuka saat pendaftaran ditutup.
     */
    public function test_tidak_ada_tautan_daftar_saat_registrasi_ditutup(): void
    {
        $this->withoutVite();
        PlatformSetting::set('registration_open', false);

        $this->get($this->hargaUrl())
            ->assertOk()
            ->assertDontSee(route('register'), false);
    }

    public function test_tidak_ada_tautan_demo_saat_demo_ditutup(): void
    {
        $this->withoutVite();
        PlatformSetting::set('demo_open', false);

        $this->get($this->hargaUrl())
            ->assertOk()
            ->assertDontSee(route('demo'), false);
    }

    public function test_kedua_pintu_tertutup_menampilkan_pesan_tanpa_cta(): void
    {
        $this->withoutVite();
        PlatformSetting::set('registration_open', false);
        PlatformSetting::set('demo_open', false);

        $this->get($this->hargaUrl())
            ->assertOk()
            ->assertDontSee(route('register'), false)
            ->assertDontSee(route('demo'), false)
            ->assertSee('sedang ditutup sementara')
            ->assertSee(route('login'), false);
    }

    /**
     * Nav & footer di sini partial yang sama dengan landing. Anchor seksi wajib
     * absolut ke landing (kalau relatif ia menggantung di halaman ini), sedangkan
     * "Harga" justru TIDAK boleh lagi jadi anchor — ia rute penuh.
     */
    public function test_nav_memakai_anchor_absolut_ke_landing(): void
    {
        $this->withoutVite();

        $this->get($this->hargaUrl())
            ->assertOk()
            ->assertSee(route('landing').'#fitur', false)
            ->assertSee(route('landing').'#faq', false)
            ->assertSee(route('harga'), false)
            ->assertSee('Hak Cipta Dilindungi');
    }
}
