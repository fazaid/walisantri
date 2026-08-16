<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\PlatformSetting;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Kriteria terima §1.8 Fase 1 — portal wali pindah ke host pesantren.
 *
 * Daftarnya diambil apa adanya dari PRD ("Kriteria terima teknis", a–f), ditambah
 * dua kasus yang lahir saat implementasi: konteks yang merender view wali DI LUAR
 * host tenant, dan pintu keluar saat langganan berakhir.
 */
class TenantHostTest extends TestCase
{
    use RefreshDatabase;

    private function pesantrenDenganWali(): array
    {
        $pesantren = Pesantren::factory()->create();
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);

        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'wali_santri_id' => $wali->id,
            'status_aktif' => true,
        ]);

        $this->hostnameTenant($pesantren);

        return [$pesantren, $wali, $santri];
    }

    /** (a) Sesi tenant B ditolak di host tenant A. */
    public function test_sesi_pesantren_lain_ditolak_di_host_tenant(): void
    {
        [$pesantrenA] = $this->pesantrenDenganWali();
        [, $waliB] = $this->pesantrenDenganWali();

        $this->actingAs($waliB)
            ->get($this->urlTenant($pesantrenA, '/wali/dashboard'))
            ->assertForbidden();
    }

    /**
     * (b) Pintu kanonik mengalihkan ke host tenant santri — TERMASUK setelah slug
     * pesantrennya diganti. Inilah yang membuat magic link lama tidak pernah mati
     * (§1.4: slug mutable, §4.3: tautan tanpa kedaluwarsa).
     */
    public function test_pintu_kanonik_mengalihkan_ke_host_tenant_walau_slug_berganti(): void
    {
        [$pesantren, , $santri] = $this->pesantrenDenganWali();

        $pesantren->update(['slug' => 'nama-baru-'.uniqid()]);
        $pesantren->domains()->update(['hostname' => $pesantren->slug.'.'.config('app.base_domain')]);

        $this->get('http://'.config('app.domain').'/report/'.$santri->uuid)
            ->assertRedirect($pesantren->fresh()->url('/report/'.$santri->uuid));
    }

    /** Tautan dibuka di host pesantren LAIN → diantar ke host yang benar, bukan dilayani. */
    public function test_magic_link_di_host_pesantren_lain_dialihkan_bukan_dilayani(): void
    {
        [, , $santri] = $this->pesantrenDenganWali();
        [$pesantrenLain] = $this->pesantrenDenganWali();

        $this->get($this->urlTenant($pesantrenLain, '/report/'.$santri->uuid))
            ->assertRedirect($santri->linkWali());

        $this->assertGuest();
    }

    /**
     * (c) Grup host tenant tidak boleh menangkap host platform. Pola
     * '{slug}.walisantri.test' juga cocok dengan 'app.walisantri.test' — yang
     * menyelamatkan hanyalah urutan pendaftaran grup di routes/web.php.
     */
    public function test_grup_host_tenant_tidak_menangkap_host_platform(): void
    {
        [, , $santri] = $this->pesantrenDenganWali();

        // Di host platform, /report/{uuid} adalah pintu kanonik (302), BUKAN
        // handler magic link (200) — kalau grupnya bocor, ia akan 200 dan login.
        $this->get('http://'.config('app.domain').'/report/'.$santri->uuid)
            ->assertRedirect();

        $this->assertGuest();

        // Dan /login di host platform tetap pintu staf.
        $this->get('http://'.config('app.domain').'/login')->assertOk();
    }

    /**
     * (d) URL portal wali harus benar dari konteks TANPA request — job queue
     * (WhatsApp/email) berjalan di luar siklus request, jadi ia tidak boleh
     * bergantung pada host yang sedang dibuka.
     */
    public function test_url_portal_wali_benar_tanpa_konteks_request(): void
    {
        [$pesantren, $wali, $santri] = $this->pesantrenDenganWali();

        // Tiru konteks queue: tidak ada default slug sama sekali.
        URL::defaults([]);

        $this->assertSame($pesantren->url('/report/'.$santri->uuid), $santri->linkWali());
        $this->assertStringContainsString($pesantren->hostname(), $wali->urlPortalWali());
    }

    /**
     * (e) Sesi magic link tidak boleh mematikan corong pendaftaran. Dengan cookie
     * ber-scope host sesi demo terkunci di host tenant, jadi /register di apex
     * tetap terbuka — inilah alasan model cookie itu dipilih.
     */
    public function test_sesi_magic_link_tidak_memblokir_pendaftaran(): void
    {
        [, , $santri] = $this->pesantrenDenganWali();

        $response = $this->get($santri->linkWali())->assertOk();

        // Yang menjaga corong pendaftaran adalah SCOPE cookie-nya, bukan logika
        // aplikasi: cookie tanpa atribut Domain hanya dikirim kembali ke host yang
        // menyetelnya, sehingga sesi demo tidak pernah terlihat di apex tempat
        // /register berada. Diuji langsung ke header-nya karena test client Laravel
        // berbagi sesi lintas host — ia tidak menirukan aturan cookie browser, jadi
        // "buka /register lalu lihat apakah tertutup" tidak membuktikan apa pun.
        $cookieSesi = collect($response->headers->getCookies())
            ->firstWhere(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($cookieSesi, 'Sesi magic link tidak menyetel cookie sama sekali.');
        $this->assertNull(
            $cookieSesi->getDomain(),
            'Cookie sesi ber-domain — ia akan ikut terkirim ke apex dan mematikan corong pendaftaran (§1.8).'
        );

        // Bahwa /register memang terbuka untuk pengunjung tanpa sesi sudah dijaga
        // RegisterControllerTest — tidak diulang di sini.
    }

    /**
     * Pagar lapis kedua, dan yang sesungguhnya menyelamatkan: sesi magic link tidak
     * dihitung sebagai "sudah login" di corong pendaftaran, TERLEPAS dari scope
     * cookie. Dilaporkan nyata di lingkungan lokal yang menyetel
     * SESSION_DOMAIN=.walisantri.test — pengunjung yang habis mencoba demo tidak
     * bisa membuka /register maupun /login, keduanya memantulkannya ke portal demo.
     *
     * Test client Laravel memang berbagi sesi lintas host, jadi di sini ia menirukan
     * lingkungan cookie-berbagi itu dengan tepat.
     */
    public function test_sesi_demo_tidak_memantulkan_pengunjung_dari_register_dan_login(): void
    {
        PlatformSetting::set('registration_open', true);

        [, , $santri] = $this->pesantrenDenganWali();

        $this->get($santri->linkWali())->assertOk();
        $this->assertTrue(session('magic_link_session'));

        $this->get('http://'.config('app.base_domain').'/register')
            ->assertOk()
            ->assertSee('id="nama-pesantren"', false);

        $this->get('http://'.config('app.domain').'/login')->assertOk();
    }

    /**
     * Keluar dari sandbox mengantar kembali ke landing, bukan ke form login
     * pesantren contoh — pengunjung tidak punya akun di sana, dan tujuannya justru
     * mendaftar (§1.8: "tombol Keluar dari demo yang eksplisit").
     */
    public function test_keluar_dari_sandbox_kembali_ke_landing(): void
    {
        [$pesantren, , $santri] = $this->pesantrenDenganWali();
        $pesantren->update(['is_demo' => true]);

        $this->get($santri->linkWali())->assertOk();

        $this->post($this->urlTenant($pesantren, '/logout'))
            ->assertRedirect(route('landing'));

        $this->assertGuest();
    }

    /**
     * Tamu di host tenant diantar ke pintu pesantrennya, bukan ke pintu platform.
     * Kalau salah, wali login di host yang keliru — sesinya lahir di sana dan
     * (karena cookie ber-scope host) tidak pernah terbaca di portal yang dituju.
     */
    public function test_tamu_di_host_tenant_diarahkan_ke_pintu_pesantrennya(): void
    {
        [$pesantren] = $this->pesantrenDenganWali();

        $this->get($this->urlTenant($pesantren, '/wali/dashboard'))
            ->assertRedirect($pesantren->url('/login'));
    }

    /**
     * Tenant tanpa baris domain membuat linkWali() jatuh ke host platform — host
     * yang sama dengan pintu kanonik itu sendiri. Tanpa pagar, pengalihannya
     * berputar tanpa henti; yang benar adalah gagal terang-terangan.
     */
    public function test_pintu_kanonik_tidak_berputar_saat_tenant_tanpa_domain(): void
    {
        $pesantren = Pesantren::factory()->create();
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'wali_santri_id' => $wali->id,
            'status_aktif' => true,
        ]);

        // Sengaja TIDAK memanggil hostnameTenant(): pesantren ini tanpa domain.
        $this->get('http://'.config('app.domain').'/report/'.$santri->uuid)
            ->assertNotFound();
    }

    /** (f) Tombol "Keluar" di portal wali berfungsi dari host tenant. */
    public function test_wali_bisa_keluar_dari_host_tenant(): void
    {
        [$pesantren, $wali] = $this->pesantrenDenganWali();

        $this->actingAs($wali)
            ->post($this->urlTenant($pesantren, '/logout'))
            ->assertRedirect($pesantren->url('/login'));

        $this->assertGuest();
    }

    /**
     * (g) Preview admin merender view wali di host PLATFORM. Tanpa default slug di
     * konteks itu, ke-38 call site route('wali.*') mati bersamaan dengan
     * "Missing required parameter".
     */
    public function test_preview_admin_tetap_merender_view_wali_di_host_platform(): void
    {
        [$pesantren, , $santri] = $this->pesantrenDenganWali();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($admin)
            ->get('http://'.config('app.domain').'/admin-preview/wali/'.$santri->id)
            ->assertOk();
    }

    /**
     * (h) Langganan berakhir: wali dikunci di host tenant, tapi pintu keluarnya
     * tetap terbuka — kalau tidak, ia terkurung dalam sesi yang tidak bisa diakhiri.
     */
    public function test_langganan_suspended_mengunci_portal_tapi_tidak_pintu_keluar(): void
    {
        [$pesantren, $wali] = $this->pesantrenDenganWali();
        $pesantren->update(['status_berlangganan' => 'suspended']);

        $this->actingAs($wali)
            ->get($this->urlTenant($pesantren, '/wali/dashboard'))
            ->assertStatus(423);

        $this->actingAs($wali)
            ->post($this->urlTenant($pesantren, '/logout'))
            ->assertRedirect($pesantren->url('/login'));
    }
}
