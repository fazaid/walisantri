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
