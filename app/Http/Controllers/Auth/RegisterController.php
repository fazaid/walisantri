<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\SlugNotReserved;
use App\Rules\ValidTenantSlug;
use App\Services\OnboardPesantren;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showForm()
    {
        abort_if(! config('app.registration_open', true), 404);

        if (Auth::check()) {
            return $this->redirectAuthenticated();
        }

        return view('auth.register');
    }

    public function store(Request $request, OnboardPesantren $onboard)
    {
        abort_if(! config('app.registration_open', true), 404);

        if (Auth::check()) {
            return $this->redirectAuthenticated();
        }

        $data = $request->validate([
            'nama_pesantren' => ['required', 'string', 'max:100'],
            'slug'           => ['required', 'string', new ValidTenantSlug, new SlugNotReserved, 'unique:pesantrens,slug'],
            'admin_name'     => ['required', 'string', 'max:100'],
            'email'          => ['required', 'email', 'unique:users,email'],
            'password'       => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $result = $onboard->execute(
            namaPesantren: $data['nama_pesantren'],
            slug:          $data['slug'],
            adminName:     $data['admin_name'],
            adminEmail:    $data['email'],
            adminPassword: $data['password'],
        );

        Auth::login($result['admin']);

        return redirect($this->adminUrl());
    }

    private function redirectAuthenticated()
    {
        if (Auth::user()->role === 'wali_santri') {
            return redirect()->route('wali.dashboard');
        }

        return redirect($this->adminUrl());
    }

    private function adminUrl(): string
    {
        return request()->getScheme() . '://' . config('app.domain') . '/admin';
    }
}
