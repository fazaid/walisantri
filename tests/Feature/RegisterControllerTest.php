<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // .env.testing menutup registrasi secara default; buka khusus untuk suite ini.
        PlatformSetting::set('registration_open', true);
    }

    private function registerUrl(): string
    {
        return 'http://' . config('app.base_domain') . '/register';
    }

    private function adminUrl(): string
    {
        return 'http://' . config('app.domain') . '/admin';
    }

    public function test_form_menampilkan_keterangan_nonaktif_saat_registrasi_ditutup(): void
    {
        $this->withoutVite();
        PlatformSetting::set('registration_open', false);

        $this->get($this->registerUrl())
            ->assertOk()
            ->assertSee('Pendaftaran Mandiri Sedang Nonaktif Sementara')
            ->assertDontSee('name="nama_pesantren"', false);

        $this->post($this->registerUrl())->assertNotFound();
    }

    public function test_guest_melihat_form_registrasi(): void
    {
        $this->withoutVite();

        $this->get($this->registerUrl())
            ->assertOk()
            ->assertSee('Daftarkan Pesantren');
    }

    public function test_wali_santri_yang_sudah_login_diarahkan_ke_dashboard_wali(): void
    {
        $wali = User::factory()->waliSantri()->create();

        $this->actingAs($wali)
            ->get($this->registerUrl())
            ->assertRedirect(route('wali.dashboard'));
    }

    public function test_admin_pesantren_yang_sudah_login_diarahkan_ke_panel_admin(): void
    {
        $admin = User::factory()->adminPesantren()->create();

        $this->actingAs($admin)
            ->get($this->registerUrl())
            ->assertRedirect($this->adminUrl());
    }

    public function test_user_yang_sudah_login_tidak_bisa_membuat_pesantren_baru_lewat_submit(): void
    {
        $admin = User::factory()->adminPesantren()->create();

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($admin)
            ->post($this->registerUrl(), [
                'nama_pesantren'        => 'Pesantren Susupan',
                'slug'                  => 'pesantren-susupan',
                'admin_name'            => 'Penyusup',
                'email'                 => 'penyusup@example.com',
                'password'              => 'Password123',
                'password_confirmation' => 'Password123',
            ])
            ->assertRedirect($this->adminUrl());

        $this->assertDatabaseMissing('pesantrens', ['slug' => 'pesantren-susupan']);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_user_yang_sudah_login_tetap_diarahkan_walau_registrasi_ditutup(): void
    {
        $admin = User::factory()->adminPesantren()->create();
        PlatformSetting::set('registration_open', false);

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($admin)
            ->post($this->registerUrl(), [
                'nama_pesantren' => 'Pesantren Susupan Ditutup',
            ])
            ->assertRedirect($this->adminUrl());

        $this->assertDatabaseMissing('pesantrens', ['nama_pesantren' => 'Pesantren Susupan Ditutup']);
    }

    public function test_guest_berhasil_mendaftar_dan_langsung_login_sebagai_admin_baru(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post($this->registerUrl(), [
                'nama_pesantren'        => 'Pesantren Al-Hidayah',
                'slug'                  => 'al-hidayah-baru',
                'admin_name'            => 'Admin Baru',
                'email'                 => 'admin-baru@example.com',
                'password'              => 'Password123',
                'password_confirmation' => 'Password123',
            ])
            ->assertRedirect($this->adminUrl());

        $this->assertDatabaseHas('pesantrens', ['slug' => 'al-hidayah-baru']);

        $pesantren = Pesantren::where('slug', 'al-hidayah-baru')->firstOrFail();

        $this->assertAuthenticated();
        $this->assertSame('admin-baru@example.com', auth()->user()->email);
        $this->assertSame($pesantren->id, auth()->user()->pesantren_id);
    }
}
