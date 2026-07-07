<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private function landingUrl(): string
    {
        return 'http://' . config('app.base_domain') . '/';
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
}
