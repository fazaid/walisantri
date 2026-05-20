<?php

// File: app/Http/Controllers/Auth/WaliLoginController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WaliLoginController extends Controller
{
    public function showLoginForm()
    {
        // Jika sudah login sebagai wali, langsung ke dashboard
        if (Auth::check() && Auth::user()->isWaliSantri()) {
            return redirect()->route('wali.dashboard');
        }

        return view('wali.auth.login');
    }

    public function login(Request $request)
    {
        // dd($request->all());

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Hanya wali santri yang boleh masuk lewat sini
            if (! $user->isWaliSantri()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akun ini bukan akun wali santri. Gunakan panel admin.',
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('wali.dashboard'));
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
}