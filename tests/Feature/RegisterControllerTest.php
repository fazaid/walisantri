<?php

namespace Tests\Feature;

use App\Models\BillingSetting;
use App\Models\Pesantren;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MenyemaiWilayah;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use MenyemaiWilayah, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // .env.testing menutup registrasi secara default; buka khusus untuk suite ini.
        PlatformSetting::set('registration_open', true);

        // Wajib: tabel wilayah kosong setelah migrate:fresh, dan /register menolak
        // kode desa yang tidak ada di sana.
        $this->semaiWilayahContoh();
    }

    private function registerUrl(): string
    {
        return 'http://'.config('app.base_domain').'/register';
    }

    private function adminUrl(): string
    {
        return 'http://'.config('app.domain').'/admin';
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

    /**
     * Pasangan dari test_landing_tidak_menjanjikan_trial: janji trial dicabut dari
     * seluruh corong, bukan hanya landing. Mekaniknya sendiri tetap jalan
     * (OnboardPesantren mengaktifkan trial Rintisan, PRD §4.1) — yang dijaga di
     * sini adalah janjinya tidak muncul kembali lewat penyuntingan copy.
     */
    public function test_form_registrasi_tidak_menjanjikan_trial(): void
    {
        $this->withoutVite();
        BillingSetting::set('trial_days', 21);

        // Kedua bentuk halaman diperiksa: yang memilih paket sendiri, dan yang
        // datang membawa paket dari kartu /harga — badge paket adalah permukaan
        // baru tempat janji itu paling mudah menyelinap kembali.
        foreach (['', '?paket=tumbuh'] as $query) {
            $this->get($this->registerUrl().$query)
                ->assertOk()
                ->assertDontSee('21 hari')
                ->assertDontSee('Trial')
                ->assertDontSee('trial');
        }
    }

    public function test_form_membuka_langkah_paket_saat_pendaftar_datang_tanpa_pilihan(): void
    {
        $this->withoutVite();

        $this->get($this->registerUrl())
            ->assertOk()
            ->assertSee('data-langkah-awal="1"', false)
            ->assertSee('name="paket"', false)
            // Maju tidak pernah dirender: kuotanya dinegosiasikan lewat tim (§5.3).
            ->assertDontSee('value="maju"', false);
    }

    public function test_paket_dari_kartu_harga_melewati_langkah_pemilihan(): void
    {
        $this->withoutVite();

        $this->get($this->registerUrl().'?paket=tumbuh')
            ->assertOk()
            ->assertSee('data-langkah-awal="2"', false)
            ->assertSee('value="tumbuh"', false)
            ->assertSee('Tumbuh');
    }

    /**
     * Paket Maju tidak boleh masuk lewat query: kuotanya angka hasil negosiasi,
     * dan halaman ini tidak punya cara menanyakannya.
     */
    public function test_paket_maju_di_query_diabaikan_dan_pendaftar_tetap_memilih(): void
    {
        $this->withoutVite();

        $this->get($this->registerUrl().'?paket=maju')
            ->assertOk()
            ->assertSee('data-langkah-awal="1"', false);
    }

    /**
     * Saran subdomain otomatis dirakit di sisi klien dan mengaitkan dua kolom
     * lewat id-nya. Menghapus salah satu id tidak akan memunculkan galat apa
     * pun — form tetap tampil, sarannya saja yang diam-diam mati.
     */
    public function test_kolom_nama_dan_slug_punya_id_yang_dipakai_saran_subdomain(): void
    {
        $this->withoutVite();

        $this->get($this->registerUrl())
            ->assertOk()
            ->assertSee('id="nama-pesantren"', false)
            ->assertSee('id="slug"', false)
            ->assertSee("getElementById('nama-pesantren')", false);
    }

    public function test_wali_santri_yang_sudah_login_diarahkan_ke_dashboard_wali(): void
    {
        $pesantren = Pesantren::factory()->create();
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);

        // Portal wali hidup di host pesantrennya (§1.8 Fase 1), sedangkan /register
        // berada di host platform — pengalihannya lintas host, jadi yang diperiksa
        // URL absolutnya, bukan route() yang butuh konteks host.
        $this->hostnameTenant($pesantren);

        $this->actingAs($wali)
            ->get($this->registerUrl())
            ->assertRedirect($pesantren->url('/wali/dashboard'));
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
                'nama_pesantren' => 'Pesantren Susupan',
                'slug' => 'pesantren-susupan',
                'admin_name' => 'Penyusup',
                'email' => 'penyusup@example.com',
                'password' => 'Password123',
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
                'paket' => 'rintisan',
                'nama_pesantren' => 'Pesantren Al-Hidayah',
                'slug' => 'al-hidayah-baru',
                'admin_name' => 'Admin Baru',
                'email' => 'admin-baru@example.com',
                'alamat_pesantren' => 'Jl. Raya Contoh No. 12, RT 03/RW 05',
                'admin_whatsapp' => '081234567890',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ] + $this->dataWilayahValid())
            ->assertRedirect();

        $this->assertDatabaseHas('pesantrens', ['slug' => 'al-hidayah-baru']);

        $pesantren = Pesantren::where('slug', 'al-hidayah-baru')->firstOrFail();

        // Sesi TIDAK lahir di apex: /register di walisantri.com, panel di
        // app.walisantri.com, dan cookie ber-scope host (§1.8 Fase 1). Sesi apex
        // hanya akan mati diam-diam dan pendaftar mendarat sebagai tamu — itulah
        // cacat yang ditutup v4.48 dengan tautan serah-terima sekali pakai.
        $this->assertGuest();

        $tautan = $this->post($this->registerUrl(), [
            'paket' => 'rintisan',
            'nama_pesantren' => 'Pesantren Kedua',
            'slug' => 'al-hidayah-kedua',
            'admin_name' => 'Admin Kedua',
            'email' => 'admin-kedua@example.com',
            'alamat_pesantren' => 'Jl. Raya Contoh No. 12, RT 03/RW 05',
            'admin_whatsapp' => '081234567891',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ] + $this->dataWilayahValid())->headers->get('Location');

        // Janji "akun aktif seketika" diuji sampai tuntas: ikuti tautannya, lalu
        // panel benar-benar terbuka sebagai admin yang baru dibuat.
        $this->get($tautan)->assertRedirect('/admin');

        $this->assertAuthenticated();
        $this->assertSame('admin-kedua@example.com', auth()->user()->email);
        $this->assertSame(
            Pesantren::where('slug', 'al-hidayah-kedua')->value('id'),
            auth()->user()->pesantren_id
        );
    }

    public function test_pendaftaran_tanpa_paket_ditolak(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post($this->registerUrl(), $this->dataPendaftaran(['paket' => null]))
            ->assertSessionHasErrors('paket');

        $this->assertDatabaseMissing('pesantrens', ['slug' => 'pesantren-uji']);
    }

    /**
     * Pagar sisi server, bukan sekadar opsi yang tidak dirender: form-nya HTML biasa
     * dan siapa pun bisa mengirim nilai apa pun.
     */
    public function test_paket_maju_ditolak_walau_dikirim_langsung(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post($this->registerUrl(), $this->dataPendaftaran(['paket' => 'maju']))
            ->assertSessionHasErrors('paket');

        $this->assertDatabaseMissing('pesantrens', ['slug' => 'pesantren-uji']);
    }

    /**
     * Inti v4.53: trial lahir pada paket yang dipilih pendaftar, bukan selalu Rintisan.
     *
     * Kuotanya sengaja digeser dari nilai bawaan lebih dulu — kalau kuota diambil dari
     * PaketLangganan::maxSantri() yang hardcode, angka di sini akan tetap 250 dan tes
     * ini merah. Sumbernya wajib BillingSetting, sama seperti /harga.
     */
    public function test_trial_lahir_pada_paket_pilihan_dengan_kuota_dari_billing_setting(): void
    {
        BillingSetting::set('kuota_tumbuh', 275);
        BillingSetting::set('trial_days', 30);

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post($this->registerUrl(), $this->dataPendaftaran(['paket' => 'tumbuh']))
            ->assertRedirect();

        $pesantren = Pesantren::where('slug', 'pesantren-uji')->firstOrFail();

        $this->assertSame('tumbuh', $pesantren->paket_langganan);
        $this->assertSame(275, $pesantren->max_santri_kuota);
        $this->assertSame('trial', $pesantren->status_berlangganan);
        $this->assertTrue($pesantren->expired_at->isSameDay(now()->addDays(30)));
    }

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function dataPendaftaran(array $ubah = []): array
    {
        // $ubah di depan: operator + mempertahankan kunci operan KIRI, jadi urutan
        // terbalik akan diam-diam mengabaikan setiap nilai yang mau ditimpa tes.
        // null = kolomnya sengaja tidak dikirim sama sekali.
        $data = $ubah + [
            'paket' => 'rintisan',
            'nama_pesantren' => 'Pesantren Uji',
            'slug' => 'pesantren-uji',
            'admin_name' => 'Admin Uji',
            'email' => 'admin-uji@example.com',
            'alamat_pesantren' => 'Jl. Raya Contoh No. 12, RT 03/RW 05',
            'admin_whatsapp' => '081234567892',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ] + $this->dataWilayahValid();

        return array_filter($data, fn ($nilai) => $nilai !== null);
    }
}
