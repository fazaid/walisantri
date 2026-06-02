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
        if (Auth::check()) {
            return redirect('/admin');
        }

        return view('auth.register');
    }

    public function store(Request $request, OnboardPesantren $onboard)
    {
        $data = $request->validate([
            'nama_pesantren' => ['required', 'string', 'max:100'],
            'slug'           => ['required', 'string', new ValidTenantSlug, new SlugNotReserved, 'unique:pesantrens,slug'],
            'admin_name'     => ['required', 'string', 'max:100'],
            'email'          => ['required', 'email', 'unique:users,email'],
            'password'       => ['required', 'confirmed', Password::min(8)],
        ]);

        $result = $onboard->execute(
            namaPesantren: $data['nama_pesantren'],
            slug:          $data['slug'],
            adminName:     $data['admin_name'],
            adminEmail:    $data['email'],
            adminPassword: $data['password'],
        );

        Auth::login($result['admin']);

        return redirect('/admin');
    }
}
