<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pesantren;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class WaliLoginController extends Controller
{
    /**
     * Dua pintu sejak §1.8 Fase 1, satu view:
     *
     * - Host tenant ({slug}.walisantri.com) — pintu wali. Pesantrennya diketahui
     *   dari host, jadi brandingnya otomatis dan form-nya mem-POST ke host yang sama.
     * - Host platform (app.walisantri.com) — pintu staf. Branding masih bisa
     *   diminta lewat ?tenant={slug} seperti sebelumnya.
     */
    public function showLoginForm(Request $request)
    {
        // Sesi magic link (mencoba demo) tidak dihitung sebagai sudah login —
        // lihat catatan panjang di RegisterController::sedangMencobaDemo().
        if (Auth::check() && ! session('magic_link_session')) {
            return $this->redirectAfterLogin(Auth::user());
        }

        $pesantrenHost = $request->attributes->get('public_pesantren');

        $pesantren = $pesantrenHost;

        if ($pesantren === null && $slug = $request->query('tenant')) {
            $pesantren = Pesantren::where('slug', $slug)->first();
        }

        // Form harus mem-POST ke host yang sedang dibuka: cookie ber-scope host,
        // jadi mengirim kredensial ke host lain menghasilkan sesi yang tidak akan
        // pernah terbaca kembali di sini.
        $aksiLogin = $pesantrenHost !== null ? route('wali.login.submit') : route('login.submit');

        return view('auth.login', compact('pesantren', 'aksiLogin'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = Str::lower($request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $pengguna = Auth::user();
            [$hostRumah] = SerahTerimaSesiController::rumah($pengguna);

            // Sejak §1.8 Fase 1 ada dua pintu: host platform untuk staf, host
            // pesantren untuk wali. Kredensialnya sama-sama sah di kedua pintu
            // (email unik global), tapi sesi yang lahir di host yang salah tidak
            // akan pernah terbaca di tujuannya — cookie ber-scope host.
            //
            // Yang SALAH adalah memantulkan pengguna ke form login satunya: ia sudah
            // mengetik kredensial yang benar, lalu disodori form yang tampak sama
            // tanpa penjelasan — dari sisi pengguna itu "login gagal". Jadi sesinya
            // diserahterimakan, bukan dipantulkan. Tokennya baru dicetak SETELAH
            // kata sandi terbukti benar, sekali pakai, dan berumur 5 menit.
            if ($hostRumah !== $request->getHost()) {
                $tautan = SerahTerimaSesiController::untuk($pengguna);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                RateLimiter::clear($throttleKey);

                return redirect()->away($tautan);
            }

            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            // regenerate() hanya mengganti ID sesi — ISINYA dipertahankan. Tanpa
            // baris ini, bendera dari sesi magic link (mis. sisa mencoba sandbox
            // publik di /coba) bertahan melewati login, dan BlockMagicLinkSession
            // akan mengunci wali yang baru saja login ke mode laporan baca-saja.
            $request->session()->forget(['magic_link_session', 'magic_link_santri_id']);

            return $this->redirectAfterLogin(Auth::user());
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'Email atau password tidak sesuai.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $diHostTenant = $request->attributes->get('public_pesantren') !== null;

        // Keluar dari sandbox mengantar kembali ke landing: menaruh pengunjung di
        // form login pesantren contoh adalah jalan buntu — ia tidak punya akun di
        // sana, dan tujuan kunjungannya justru mendaftar.
        $keluarDariDemo = session('magic_link_session')
            && (Auth::user()?->pesantren?->is_demo ?? false);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Pulang ke pintu host yang sama. Mengembalikan wali ke app.walisantri.com
        // berarti melempar mereka keluar dari permukaan ber-merek pesantrennya —
        // persis yang §1.8 ingin hindari.
        if ($keluarDariDemo) {
            return redirect()->away(url()->route('landing'));
        }

        return redirect()->route($diHostTenant ? 'wali.login' : 'login');
    }

    private function redirectAfterLogin($user)
    {
        return match ($user->role) {
            // Portal wali hidup di host pesantren (§1.8 Fase 1) dan konteks ini berjalan
            // di host platform — route('wali.dashboard') di sini akan gagal karena tidak
            // punya default slug. Bangun URL-nya dari tenant-nya sendiri.
            'wali_santri' => redirect()->away($user->urlPortalWali()),
            'super_admin' => redirect('/admin'),
            default => redirect('/admin'),
        };
    }
}
