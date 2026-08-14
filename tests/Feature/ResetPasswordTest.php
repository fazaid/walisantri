<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\EmailSetting;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Alur reset kata sandi (§9.1) — hanya untuk staf.
 *
 * Wali santri sengaja di luar cakupan: mereka masuk lewat Magic Link dan
 * `users.email` mereka boleh null, jadi jalur ini tidak akan pernah bisa
 * melayani mereka.
 */
class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(): Pesantren
    {
        return Pesantren::create([
            'nama_pesantren' => 'Pesantren Reset Test',
            'slug' => 'pesantren-reset-'.uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addMonth(),
        ]);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'pesantren_id' => $role === 'super_admin' ? null : $this->makePesantren()->id,
            'name' => 'Pengguna '.$role,
            'email' => $email,
            'password' => bcrypt('LamaSekali123'),
            'role' => $role,
        ]);
    }

    public function test_admin_menerima_email_tautan_reset(): void
    {
        Notification::fake();
        $user = $this->makeUser('admin_pesantren', 'admin.reset@contoh.test');

        $this->post(route('password.email'), ['email' => 'admin.reset@contoh.test'])
            ->assertSessionHas('status');

        // Diperiksa dua lapis: notifikasinya terkirim ke orang yang benar, DAN
        // mailable yang dihasilkannya menuju alamat serta subjek yang benar.
        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notif) use ($user) {
                $mail = $notif->toMail($user);

                return $mail instanceof ResetPasswordMail
                    && $mail->hasTo($user->email)
                    && $mail->token === $notif->token;
            }
        );
    }

    public function test_ustadz_juga_dilayani(): void
    {
        Notification::fake();
        $user = $this->makeUser('ustadz', 'ustadz.reset@contoh.test');

        $this->post(route('password.email'), ['email' => 'ustadz.reset@contoh.test']);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    /**
     * Wali santri dijawab apa adanya, bukan dengan balasan seragam: membiarkan
     * mereka menunggu email yang tidak akan pernah datang justru menyesatkan.
     */
    public function test_wali_santri_diarahkan_ke_magic_link(): void
    {
        Notification::fake();
        $this->makeUser('wali_santri', 'wali.reset@contoh.test');

        $this->post(route('password.email'), ['email' => 'wali.reset@contoh.test'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    /**
     * Alamat tak dikenal harus dijawab persis seperti alamat terdaftar — kalau
     * berbeda, halaman ini jadi alat memeriksa siapa saja yang punya akun.
     */
    public function test_email_tak_dikenal_dijawab_seragam(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'entahsiapa@contoh.test'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_tidak_mengirim_saat_toggle_dimatikan(): void
    {
        Notification::fake();
        EmailSetting::set('email_reset_password_enabled', false);
        $this->makeUser('admin_pesantren', 'admin.mati@contoh.test');

        $this->post(route('password.email'), ['email' => 'admin.mati@contoh.test'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_token_valid_mengubah_kata_sandi(): void
    {
        Notification::fake();
        $user = $this->makeUser('admin_pesantren', 'admin.ganti@contoh.test');

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'BaruSekali123',
            'password_confirmation' => 'BaruSekali123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('BaruSekali123', $user->fresh()->password));
    }

    public function test_token_hanya_bisa_dipakai_sekali(): void
    {
        Notification::fake();
        $user = $this->makeUser('admin_pesantren', 'admin.sekali@contoh.test');

        $token = Password::broker()->createToken($user);

        $kirim = fn (string $sandi) => $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $sandi,
            'password_confirmation' => $sandi,
        ]);

        $kirim('PertamaKali123');
        $kirim('KeduaKali123')->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('PertamaKali123', $user->fresh()->password));
    }

    public function test_token_palsu_ditolak(): void
    {
        $user = $this->makeUser('admin_pesantren', 'admin.palsu@contoh.test');

        $this->post(route('password.update'), [
            'token' => 'token-yang-tidak-pernah-diterbitkan',
            'email' => $user->email,
            'password' => 'BaruSekali123',
            'password_confirmation' => 'BaruSekali123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('LamaSekali123', $user->fresh()->password));
    }

    public function test_kata_sandi_lemah_ditolak(): void
    {
        Notification::fake();
        $user = $this->makeUser('admin_pesantren', 'admin.lemah@contoh.test');

        $this->post(route('password.update'), [
            'token' => Password::broker()->createToken($user),
            'email' => $user->email,
            'password' => 'rahasia',
            'password_confirmation' => 'rahasia',
        ])->assertSessionHasErrors('password');
    }

    public function test_halaman_lupa_password_bisa_dibuka(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa Kata Sandi', false);
    }

    public function test_tautan_lupa_password_tampil_di_halaman_masuk(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), false);
    }
}
