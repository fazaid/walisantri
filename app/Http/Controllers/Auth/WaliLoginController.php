<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pesantren;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WaliLoginController extends Controller
{
    /**
     * Login terpusat (§1.3): semua role masuk ke app.walisantri.com/login.
     * ?tenant={slug} → baca branding pesantren agar halaman terasa gerbang lembaga.
     */
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin(Auth::user());
        }

        $pesantren = null;
        if ($slug = $request->query('tenant')) {
            $pesantren = Pesantren::where('slug', $slug)->first();
        }

        return view('auth.login', compact('pesantren'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->redirectAfterLogin(Auth::user());
        }

        return back()->withErrors([
            'email' => 'Email atau password tidak sesuai.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('wali.login');
    }

    private function redirectAfterLogin($user)
    {
        return match ($user->role) {
            'wali_santri'    => redirect()->route('wali.dashboard'),
            'super_admin'    => redirect()->to(
                'http://' . config('app.dash_domain') . '/admin'
            ),
            default          => redirect('/admin'),
        };
    }
}
