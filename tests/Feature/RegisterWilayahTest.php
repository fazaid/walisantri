<?php

namespace Tests\Feature;

use App\Enums\OnboardingStep;
use App\Models\Pesantren;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\MenyemaiWilayah;
use Tests\TestCase;

/**
 * Wizard dua langkah di /register dan kolom wilayah/kontak yang dibawanya (§4.1).
 *
 * Dipisah dari RegisterControllerTest supaya berkas lama tetap fokus pada corong
 * pendaftaran itu sendiri (buka/tutup, redirect, serah terima sesi).
 */
class RegisterWilayahTest extends TestCase
{
    use MenyemaiWilayah, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PlatformSetting::set('registration_open', true);
        $this->semaiWilayahContoh();
    }

    private function registerUrl(): string
    {
        return 'http://'.config('app.base_domain').'/register';
    }

    /**
     * @param  array<string, mixed>  $ubahan
     * @return array<string, mixed>
     */
    private function payload(array $ubahan = []): array
    {
        return array_merge([
            'paket' => 'rintisan',
            'nama_pesantren' => 'Pesantren Al-Hidayah',
            'slug' => 'al-hidayah-wilayah',
            'admin_name' => 'Admin Baru',
            'email' => 'admin-wilayah@example.com',
            'alamat_pesantren' => 'Jl. Raya Contoh No. 12, RT 03/RW 05',
            'admin_whatsapp' => '081234567890',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ] + $this->dataWilayahValid(), $ubahan);
    }

    /**
     * @param  array<string, mixed>  $ubahan
     */
    private function daftar(array $ubahan = []): TestResponse
    {
        return $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post($this->registerUrl(), $this->payload($ubahan));
    }

    // ---------------------------------------------------------------- tampilan

    public function test_form_terbagi_dua_langkah(): void
    {
        $this->withoutVite();

        $this->get($this->registerUrl())
            ->assertOk()
            ->assertSee('id="langkah-1"', false)
            ->assertSee('id="langkah-2"', false)
            ->assertSee('Data Pesantren')
            ->assertSee('Penanggung Jawab')
            ->assertSee('Lanjut');
    }

    public function test_select_provinsi_dirender_dari_tabel_wilayah(): void
    {
        $this->withoutVite();

        $this->get($this->registerUrl())
            ->assertOk()
            ->assertSee('name="wilayah_provinsi"', false)
            ->assertSee('value="32"', false)
            ->assertSee('Jawa Barat')
            // Tiga select bawah dimuat JS — tidak boleh ikut dirender penuh.
            ->assertDontSee('value="32.01.01.1006"', false);
    }

    /**
     * Duplikasi sengaja dari RegisterControllerTest: id-nya mengait saran subdomain
     * di sisi klien, dan berkas inilah yang paling mungkin menyunting markup form.
     */
    public function test_id_nama_pesantren_dan_slug_bertahan_setelah_wizard(): void
    {
        $this->withoutVite();

        $this->get($this->registerUrl())
            ->assertOk()
            ->assertSee('id="nama-pesantren"', false)
            ->assertSee('id="slug"', false);
    }

    // --------------------------------------------------------------- persistensi

    public function test_pendaftaran_menyimpan_wilayah_lengkap_ke_profil(): void
    {
        $this->daftar()->assertRedirect();

        $profil = Pesantren::where('slug', 'al-hidayah-wilayah')->sole()->profil;

        // assertEquals, bukan assertSame: jsonb PostgreSQL menormalkan urutan key saat
        // menyimpan (di SQLite urutannya utuh), jadi perbandingan yang peka urutan hijau
        // di lokal lalu merah di CI.
        $this->assertEquals([
            'provinsi' => ['kode' => '32', 'nama' => 'Jawa Barat'],
            'kota' => ['kode' => '32.01', 'nama' => 'Kabupaten Bogor'],
            'kecamatan' => ['kode' => '32.01.01', 'nama' => 'Cibinong'],
            'desa' => ['kode' => '32.01.01.1006', 'nama' => 'Cibinong'],
        ], $profil['wilayah']);
    }

    public function test_nomor_wa_admin_disimpan_ternormalisasi(): void
    {
        $this->daftar(['admin_whatsapp' => '0812-3456-7890'])->assertRedirect();

        $this->assertSame('6281234567890', User::where('email', 'admin-wilayah@example.com')->sole()->phone_number);
    }

    public function test_telepon_dan_email_pesantren_tersimpan_saat_diisi(): void
    {
        $this->daftar([
            'telepon_pesantren' => '0251 1234567',
            'email_pesantren' => 'info@alhidayah.test',
        ])->assertRedirect();

        $profil = Pesantren::where('slug', 'al-hidayah-wilayah')->sole()->profil;

        $this->assertSame('0251 1234567', $profil['telepon']);
        // Key `email_kontak`, bukan `email_pesantren` — itulah yang dirender profil publik.
        $this->assertSame('info@alhidayah.test', $profil['email_kontak']);
    }

    public function test_telepon_dan_email_pesantren_boleh_kosong(): void
    {
        $this->daftar()->assertRedirect();

        $profil = Pesantren::where('slug', 'al-hidayah-wilayah')->sole()->profil;

        $this->assertArrayNotHasKey('telepon', $profil);
        $this->assertArrayNotHasKey('email_kontak', $profil);
    }

    public function test_alamat_pesantren_tersimpan_apa_adanya(): void
    {
        $this->daftar()->assertRedirect();

        $pesantren = Pesantren::where('slug', 'al-hidayah-wilayah')->sole();

        // Diambil dari kolomnya sendiri, BUKAN dirangkai dari wilayah — nilai yang
        // dirangkai otomatis akan ditimpa begitu admin menyunting alamat sungguhan.
        $this->assertSame('Jl. Raya Contoh No. 12, RT 03/RW 05', $pesantren->profil['alamat']);
    }

    /**
     * Langkah onboarding Profil menuntut alamat DAN logo (§14). Alamat kini terisi sejak
     * pendaftaran, logo tidak bisa diunggah dari /register — jadi langkahnya tetap
     * terbuka, dan checklist tenant lama tidak ikut berubah perilakunya.
     */
    public function test_onboarding_profil_belum_selesai_walau_alamat_sudah_terisi(): void
    {
        $this->daftar()->assertRedirect();

        $pesantren = Pesantren::where('slug', 'al-hidayah-wilayah')->sole();

        $this->assertSame([], $pesantren->onboarding_completed_steps);
        $this->assertFalse($pesantren->hasCompletedOnboardingStep(OnboardingStep::Profil));
    }

    public function test_alamat_pesantren_wajib_diisi(): void
    {
        $this->daftar(['alamat_pesantren' => ''])->assertSessionHasErrors('alamat_pesantren');

        $this->assertDatabaseMissing('pesantrens', ['slug' => 'al-hidayah-wilayah']);
    }

    /**
     * Alamat jalan dan wilayah adalah dua kolom terpisah, jadi profil publik harus
     * menggabungkannya — alamat sendirian tidak lagi memuat kota maupun provinsi.
     */
    public function test_alamat_lengkap_menggabungkan_jalan_dan_wilayah(): void
    {
        $this->daftar()->assertRedirect();

        $this->assertSame(
            'Jl. Raya Contoh No. 12, RT 03/RW 05, Cibinong, Kec. Cibinong, Kabupaten Bogor, Jawa Barat',
            Pesantren::where('slug', 'al-hidayah-wilayah')->sole()->alamatLengkap()
        );
    }

    // ---------------------------------------------------------------- validasi

    public function test_wilayah_wajib_diisi(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post($this->registerUrl(), array_diff_key($this->payload(), $this->dataWilayahValid()))
            ->assertSessionHasErrors(['wilayah_provinsi', 'wilayah_kota', 'wilayah_kecamatan', 'wilayah_desa']);

        $this->assertDatabaseMissing('pesantrens', ['slug' => 'al-hidayah-wilayah']);
    }

    public function test_kode_desa_tak_dikenal_ditolak(): void
    {
        $this->daftar([
            'wilayah_provinsi' => '99',
            'wilayah_kota' => '99.99',
            'wilayah_kecamatan' => '99.99.99',
            'wilayah_desa' => '99.99.99.9999',
        ])->assertSessionHasErrors('wilayah_desa');

        $this->assertDatabaseMissing('pesantrens', ['slug' => 'al-hidayah-wilayah']);
    }

    /**
     * Inti keputusan §4.1: leluhur diturunkan dari kode desa, bukan dipercaya dari
     * klien. Payload yang disunting harus ditolak, bukan diam-diam "diperbaiki".
     */
    public function test_kombinasi_wilayah_tidak_konsisten_ditolak(): void
    {
        $this->daftar(['wilayah_provinsi' => '33'])->assertSessionHasErrors('wilayah_desa');

        $this->daftar(['wilayah_kecamatan' => '33.01.01'])->assertSessionHasErrors('wilayah_desa');

        $this->assertDatabaseMissing('pesantrens', ['slug' => 'al-hidayah-wilayah']);
    }

    public function test_nomor_wa_wajib_dan_harus_masuk_akal(): void
    {
        $this->daftar(['admin_whatsapp' => ''])->assertSessionHasErrors('admin_whatsapp');
        $this->daftar(['admin_whatsapp' => 'abc'])->assertSessionHasErrors('admin_whatsapp');
        // Lolos regex (8 karakter) tapi terlalu pendek untuk dinormalkan Fonnte.
        $this->daftar(['admin_whatsapp' => '0812 345'])->assertSessionHasErrors('admin_whatsapp');

        $this->assertDatabaseMissing('pesantrens', ['slug' => 'al-hidayah-wilayah']);
    }

    public function test_email_pesantren_harus_berformat_email(): void
    {
        $this->daftar(['email_pesantren' => 'bukan-email'])->assertSessionHasErrors('email_pesantren');
    }

    // ------------------------------------------------------- langkah saat galat

    public function test_form_dibuka_di_langkah_penanggung_jawab_saat_galatnya_di_sana(): void
    {
        $this->withoutVite();
        $pesantren = Pesantren::factory()->create();
        User::factory()->create(['pesantren_id' => $pesantren->id, 'email' => 'sudah@ada.test']);

        // from() menetapkan URL sebelumnya supaya back() dari validasi mendarat di
        // /register, bukan di landing.
        $this->from($this->registerUrl())
            ->followingRedirects()
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post($this->registerUrl(), $this->payload(['email' => 'sudah@ada.test']))
            ->assertSee('data-langkah-awal="3"', false);
    }

    public function test_form_dibuka_di_langkah_data_pesantren_saat_galatnya_di_sana(): void
    {
        $this->withoutVite();
        Pesantren::factory()->create(['slug' => 'sudah-dipakai']);

        $this->from($this->registerUrl())
            ->followingRedirects()
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post($this->registerUrl(), $this->payload(['slug' => 'sudah-dipakai']))
            ->assertSee('data-langkah-awal="2"', false);
    }
}
