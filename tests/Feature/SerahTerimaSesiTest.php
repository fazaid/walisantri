<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\SerahTerimaSesiController;
use App\Models\Pesantren;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MenyemaiWilayah;
use Tests\TestCase;

/**
 * Serah-terima sesi lintas host di akhir pendaftaran (§1.8 Fase 1).
 *
 * `/register` hidup di apex, panel staf di host app, dan cookie sesi ber-scope host.
 * Tanpa mekanisme ini, `Auth::login()` saat pendaftaran menghasilkan sesi yang tidak
 * pernah terbaca di panel — pendaftar mendarat sebagai tamu, padahal halaman
 * pendaftaran menjanjikan "akun aktif seketika". Cacat itu SUDAH ADA sebelum v4.48
 * dan hanya tersembunyi di lokal yang dulu memakai cookie berbagi.
 */
class SerahTerimaSesiTest extends TestCase
{
    use MenyemaiWilayah, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Wajib: tabel wilayah kosong setelah migrate:fresh, dan /register menolak
        // kode desa yang tidak ada di sana.
        $this->semaiWilayahContoh();
    }

    private function dataPendaftaran(string $slug): array
    {
        return [
            'paket' => 'rintisan',
            'nama_pesantren' => 'Pesantren '.$slug,
            'slug' => $slug,
            'admin_name' => 'Admin Uji',
            'email' => $slug.'@contoh.test',
            'alamat_pesantren' => 'Jl. Raya Contoh No. 12, RT 03/RW 05',
            'admin_whatsapp' => '081234567890',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ] + $this->dataWilayahValid();
    }

    public function test_pendaftaran_mengantar_lewat_tautan_serah_terima_bukan_sesi_apex(): void
    {
        PlatformSetting::set('registration_open', true);

        $response = $this->post('http://'.config('app.base_domain').'/register', $this->dataPendaftaran('uji-serah'));

        // Sesi TIDAK dibuat di apex — kalau dibuat, ia hanya akan mati diam-diam.
        $this->assertGuest();

        $tujuan = $response->headers->get('Location');

        $this->assertStringContainsString(config('app.domain').'/masuk-otomatis/', $tujuan);
        $this->assertStringContainsString('signature=', $tujuan);
    }

    public function test_tautan_serah_terima_memasukkan_admin_ke_panel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $this->get(SerahTerimaSesiController::untuk($admin))
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_tautan_serah_terima_hanya_sekali_pakai(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $tautan = SerahTerimaSesiController::untuk($admin);

        $this->get($tautan)->assertRedirect('/admin');

        // Tautan yang tersimpan di riwayat browser atau ter-forward tidak boleh
        // menjadi pintu masuk permanen tanpa kata sandi.
        $this->post('http://'.config('app.domain').'/logout');

        $this->get($tautan)->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Login lintas pintu diserahterimakan, BUKAN dipantulkan.
     *
     * Sejak §1.8 Fase 1 ada dua pintu login dan kredensialnya sah di keduanya
     * (email unik global). Memantulkan pengguna ke form login satunya membuatnya
     * mengetik kredensial yang benar lalu disodori form yang tampak sama tanpa
     * penjelasan — dari sisi pengguna itu "tidak bisa login". Dilaporkan nyata.
     */
    public function test_wali_yang_login_di_pintu_staf_diserahterimakan_ke_portalnya(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->hostnameTenant($pesantren);

        $wali = User::factory()->waliSantri()->create([
            'pesantren_id' => $pesantren->id,
            'password' => bcrypt('Rahasia123'),
        ]);

        $tautan = $this->post('http://'.config('app.domain').'/login', [
            'email' => $wali->email,
            'password' => 'Rahasia123',
        ])->headers->get('Location');

        $this->assertStringContainsString($pesantren->hostname().'/masuk-otomatis/', $tautan);

        // Sesi TIDAK ditinggalkan di pintu staf.
        $this->assertGuest();

        $this->get($tautan)->assertRedirect('/wali/dashboard');
        $this->assertAuthenticatedAs($wali);
    }

    public function test_staf_yang_login_di_host_tenant_diserahterimakan_ke_panel(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->hostnameTenant($pesantren);

        $admin = User::factory()->adminPesantren()->create([
            'pesantren_id' => $pesantren->id,
            'password' => bcrypt('Rahasia123'),
        ]);

        $tautan = $this->post($pesantren->url('/login'), [
            'email' => $admin->email,
            'password' => 'Rahasia123',
        ])->headers->get('Location');

        $this->assertStringContainsString(config('app.domain').'/masuk-otomatis/', $tautan);

        $this->get($tautan)->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_tanda_tangan_palsu_ditolak(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $tautan = SerahTerimaSesiController::untuk($admin);

        $this->get($tautan.'x')->assertForbidden();
        $this->assertGuest();
    }
}
