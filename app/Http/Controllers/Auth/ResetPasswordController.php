<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Reset kata sandi lewat email (PRD §9.1).
 *
 * Route-nya berdiri sendiri, bukan `->passwordReset()` milik panel Filament:
 * login dipusatkan di /login non-Filament, dan mendaftarkan auth Filament akan
 * memunculkan halaman kedua yang bersaing dengan WaliLoginController.
 */
class ResetPasswordController extends Controller
{
    /**
     * Jawaban yang SELALU sama, apa pun kenyataannya.
     *
     * Kalau alamat tak dikenal dijawab berbeda dari alamat terdaftar, halaman ini
     * berubah jadi alat memeriksa siapa saja yang punya akun di sini.
     */
    private const BALASAN_SERAGAM = 'Bila alamat tersebut terdaftar sebagai akun staf, tautan untuk mengatur ulang kata sandi sudah kami kirim. Periksa juga folder spam.';

    private const PESAN_WALI = 'Akun wali santri tidak memakai kata sandi. Masuklah lewat tautan portal yang diberikan pesantren Anda.';

    public function showLinkRequestForm()
    {
        return view('auth.lupa-password');
    }

    public function sendResetLink(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        if (! EmailSetting::get('email_reset_password_enabled')) {
            return back()->withInput()->withErrors([
                'email' => 'Pemulihan kata sandi lewat email sedang dinonaktifkan. Hubungi admin pesantren Anda untuk direset manual.',
            ]);
        }

        $user = User::where('email', $data['email'])->first();

        // Wali santri sengaja diberi tahu apa adanya, bukan dijawab seragam:
        // mereka memang tidak punya kata sandi, dan membiarkan mereka menunggu
        // email yang tidak akan pernah datang justru menyesatkan.
        if ($user && $user->role === 'wali_santri') {
            return back()->withInput()->withErrors(['email' => self::PESAN_WALI]);
        }

        if ($user) {
            Password::broker()->sendResetLink(['email' => $data['email']]);
        }

        return back()->with('status', self::BALASAN_SERAGAM);
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::broker()->reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($status !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Tautan sudah kedaluwarsa atau pernah dipakai. Silakan minta tautan baru.',
            ]);
        }

        return redirect()->route('login')->with(
            'status',
            'Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru Anda.'
        );
    }
}
