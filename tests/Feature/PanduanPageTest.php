<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanduanPageTest extends TestCase
{
    use RefreshDatabase;

    private function panduanUrl(): string
    {
        return 'http://'.config('app.domain').'/panduan';
    }

    private function panduanUrlDomainUtama(): string
    {
        return 'http://'.config('app.base_domain').'/panduan';
    }

    public function test_panduan_terbuka_di_kedua_domain_tanpa_login(): void
    {
        $this->withoutVite();

        $this->get($this->panduanUrl())->assertOk()->assertSee('Panduan Penggunaan');
        $this->get($this->panduanUrlDomainUtama())->assertOk()->assertSee('Panduan Penggunaan');
    }

    /**
     * Nav & footer halaman ini kini partial yang sama dengan landing. Yang diperiksa
     * bukan warnanya (itu urusan CSS), melainkan buktinya: menu landing muncul di
     * sini, dan tautan seksinya absolut ke landing — kalau relatif, "#harga" hanya
     * menggantung di halaman panduan dan tidak membawa pembaca ke mana pun.
     */
    public function test_nav_dan_footer_sama_dengan_landing_dengan_anchor_absolut(): void
    {
        $this->withoutVite();

        $this->get($this->panduanUrl())
            ->assertOk()
            ->assertSee('Cara Kerja')
            ->assertSee(route('landing').'#harga', false)
            ->assertSee(route('landing').'#faq', false)
            ->assertSee('Hak Cipta Dilindungi');
    }

    /**
     * Nav-nya sticky, dan elemen sticky terkurung di blok induknya: pembungkus
     * <div class="site-chrome"> yang dulu ada di sini setinggi nav persis, sehingga
     * nav tidak punya ruang untuk menempel dan langsung tergulir hilang — daftar isi
     * di bawahnya lalu menggantung di ruang kosong setinggi --nav-h. Dikunci di sini
     * karena gejalanya (daftar isi mengambang) tidak menuntun ke penyebabnya.
     */
    public function test_nav_tidak_dibungkus_pengurung_sticky(): void
    {
        $this->withoutVite();

        $isi = $this->get($this->panduanUrl())->assertOk()->getContent();

        // Yang dijaga markup-nya, bukan kata "site-chrome" — komentar di <style>
        // halaman itu sengaja masih menyebutnya sebagai catatan jebakan.
        $this->assertStringNotContainsString('class="site-chrome"', $isi);
        // Sisipan analitik (skrip/noscript) boleh mendahului; yang tidak boleh
        // adalah elemen tampak yang membungkus nav.
        $this->assertMatchesRegularExpression(
            '/<body[^>]*>(\s|<!--.*?-->|<script\b.*?<\/script>|<noscript\b.*?<\/noscript>)*<nav/s',
            $isi,
            'Nav harus jadi elemen tampak pertama di <body>, tanpa pembungkus yang mengurung sticky-nya.'
        );
    }

    /**
     * Sebelum nav dijadikan partial bersama, /panduan memakai rute `Route::view`
     * tanpa controller sehingga tautan Demo & Masuk di sini tidak pernah ikut
     * gerbang mana pun: menutup pendaftaran menyisakan pintu yang masih terbuka
     * di halaman ini. Dua tes berikut menjaga agar itu tidak kembali.
     */
    public function test_tautan_demo_hilang_dari_panduan_saat_demo_ditutup(): void
    {
        $this->withoutVite();
        PlatformSetting::set('demo_open', false);

        $this->get($this->panduanUrl())
            ->assertOk()
            ->assertDontSee(route('demo'), false);
    }

    public function test_tautan_daftar_hilang_dari_panduan_saat_registrasi_ditutup(): void
    {
        $this->withoutVite();
        PlatformSetting::set('registration_open', false);

        $this->get($this->panduanUrl())
            ->assertOk()
            ->assertDontSee(route('register'), false);
    }
}
