<?php

namespace Tests\Feature;

use App\Mail\VerifikasiEmail;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\VerifikasiEmailNotification;
use App\Support\TautanVerifikasiEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Verifikasi email LUNAK (§12.2): menghasilkan sinyal, tidak memblokir apa pun.
 *
 * Yang dikunci di sini bukan cuma "tautannya bekerja", tapi juga batas cakupannya
 * — kalau suatu saat verifikasi berubah jadi gating, itu harus keputusan sadar
 * yang menggugurkan tes ini, bukan efek samping refactor.
 */
class VerifikasiEmailTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(): Pesantren
    {
        return Pesantren::create([
            'nama_pesantren' => 'Pesantren Verifikasi',
            'slug' => 'pesantren-verif-'.uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addMonth(),
        ]);
    }

    private function makeUser(string $role = 'admin_pesantren', bool $terverifikasi = false): User
    {
        static $counter = 0;
        $counter++;

        $user = User::create([
            'pesantren_id' => $role === 'super_admin' ? null : $this->makePesantren()->id,
            'name' => "Pengguna Verif {$counter}",
            'email' => "verif.{$counter}@contoh.test",
            'password' => bcrypt('password'),
            'role' => $role,
        ]);

        // markEmailAsVerified(), bukan diselipkan ke User::create(): kolomnya tidak
        // terdaftar di #[Fillable], jadi mass-assign akan diam-diam mengabaikannya
        // dan tes ini lulus karena alasan yang salah.
        if ($terverifikasi) {
            $user->markEmailAsVerified();
        }

        return $user;
    }

    // ------------------------------------------------------------ tautan verify

    public function test_tautan_bertanda_tangan_menandai_email_terverifikasi(): void
    {
        $user = $this->makeUser();

        $this->get(TautanVerifikasiEmail::untuk($user))->assertRedirect('/admin');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_tautan_tanpa_tanda_tangan_ditolak(): void
    {
        $user = $this->makeUser();

        $this->get(route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]))->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_tautan_kedaluwarsa_ditolak(): void
    {
        $user = $this->makeUser();

        $url = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    /**
     * Hash dibuat dari alamat email, jadi tautan yang sudah terlanjur dikirim
     * harus mati sendiri begitu alamatnya diganti — kalau tidak, alamat lama yang
     * mungkin sudah bukan milik orang itu masih bisa "membuktikan" kepemilikan.
     */
    public function test_tautan_mati_setelah_alamat_email_diubah(): void
    {
        $user = $this->makeUser();
        $url = TautanVerifikasiEmail::untuk($user);

        $user->forceFill(['email' => 'alamat.baru@contoh.test'])->save();

        $this->get($url)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_membuka_dua_kali_tidak_menggeser_stempel_waktu(): void
    {
        $user = $this->makeUser();
        $url = TautanVerifikasiEmail::untuk($user);

        $this->get($url);
        $pertama = $user->fresh()->email_verified_at;

        $this->travel(5)->minutes();
        $this->get($url)->assertRedirect('/admin');

        $this->assertEquals($pertama, $user->fresh()->email_verified_at);
    }

    // -------------------------------------------------------------- kirim ulang

    public function test_kirim_ulang_mengirim_notifikasi_verifikasi(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('verification.send'))->assertRedirect();

        Notification::assertSentTo($user, VerifikasiEmailNotification::class);
    }

    public function test_kirim_ulang_menghasilkan_mailable_ke_alamat_yang_benar(): void
    {
        $user = $this->makeUser();

        $mail = (new VerifikasiEmailNotification)->toMail($user);

        $this->assertInstanceOf(VerifikasiEmail::class, $mail);
        $this->assertTrue($mail->hasTo($user->email));
    }

    public function test_kirim_ulang_tidak_mengirim_untuk_yang_sudah_terverifikasi(): void
    {
        Notification::fake();
        $user = $this->makeUser(terverifikasi: true);

        $this->actingAs($user)->post(route('verification.send'));

        Notification::assertNothingSent();
    }

    public function test_kirim_ulang_kena_rate_limit(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->post(route('verification.send'))->assertRedirect();
        }

        $this->actingAs($user)->post(route('verification.send'))->assertStatus(429);
    }

    public function test_tamu_tidak_bisa_memicu_kirim_ulang(): void
    {
        Notification::fake();

        $this->post(route('verification.send'))->assertRedirect(route('login'));

        Notification::assertNothingSent();
    }

    // ------------------------------------------------------------------ spanduk

    public function test_spanduk_tampil_untuk_admin_belum_terverifikasi(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/admin')
            ->assertOk()
            ->assertSee('belum dikonfirmasi', false);
    }

    public function test_spanduk_tidak_tampil_untuk_admin_terverifikasi(): void
    {
        $this->actingAs($this->makeUser(terverifikasi: true))
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('belum dikonfirmasi', false);
    }

    /**
     * Ustadz sengaja di luar cakupan: alamat mereka diketik admin, bukan diri
     * sendiri, dan belum ada satu pun email platform yang menyasar mereka.
     */
    public function test_spanduk_tidak_tampil_untuk_ustadz(): void
    {
        $this->actingAs($this->makeUser('ustadz'))
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('belum dikonfirmasi', false);
    }

    /**
     * Inti keputusan "lunak": belum terverifikasi TIDAK boleh menghalangi apa pun.
     * Kalau tes ini suatu saat gagal, gating sudah masuk diam-diam.
     */
    public function test_admin_belum_terverifikasi_tetap_bisa_memakai_panel(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Verify Your Email Address', false);
    }

    // ------------------------------------------------------------------ backfill

    /**
     * Migrasi tambalan menandai staf yang sudah ada sebagai terverifikasi — kalau
     * tidak, 21 pengguna aktif akan disambut spanduk "konfirmasi alamat email"
     * padahal alamat merekalah yang selama ini dipakai.
     *
     * Migrasinya sudah berjalan saat RefreshDatabase menyiapkan basis data, jadi
     * yang diuji di sini adalah pengulangan logikanya terhadap baris baru — bukan
     * migrasinya sendiri, yang tidak bisa dijalankan ulang.
     */
    public function test_backfill_hanya_menyentuh_admin_dan_super_admin(): void
    {
        $admin = $this->makeUser('admin_pesantren');
        $superAdmin = $this->makeUser('super_admin');
        $ustadz = $this->makeUser('ustadz');
        $wali = $this->makeUser('wali_santri');

        DB::table('users')
            ->whereIn('role', ['admin_pesantren', 'super_admin'])
            ->whereNotNull('email')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        $this->assertNotNull($admin->fresh()->email_verified_at);
        $this->assertNotNull($superAdmin->fresh()->email_verified_at);

        // Alamat ustadz & wali diketik admin, bukan diri sendiri, dan belum ada
        // email platform yang menyasar mereka — menandainya terverifikasi berarti
        // berbohong tentang sesuatu yang mungkin dipakai nanti.
        $this->assertNull($ustadz->fresh()->email_verified_at);
        $this->assertNull($wali->fresh()->email_verified_at);
    }
}
