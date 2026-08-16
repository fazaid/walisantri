<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SambutanPendaftaran;
use App\Models\EmailSetting;
use App\Models\Pesantren;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Rules\SlugNotReserved;
use App\Rules\ValidTenantSlug;
use App\Services\OnboardPesantren;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * Sesi magic link BUKAN "pengguna yang sudah login" di corong pendaftaran.
     *
     * Pengunjung yang mengetuk /coba benar-benar di-login-kan sebagai wali tenant
     * demo (`VerifyMagicToken` memanggil `Auth::login()`) — sifat read-only-nya
     * ditegakkan `BlockMagicLinkSession`, bukan oleh ketiadaan sesi. Kalau sesi itu
     * sampai terlihat di apex, calon pelanggan yang baru mencoba demo tidak akan
     * pernah bisa membuka form pendaftaran: ia dipantulkan ke portal demo.
     *
     * Hari ini yang menahannya cookie ber-scope host (§1.8 Fase 1) — tapi itu satu
     * variabel env dari rusak, dan `SESSION_DOMAIN=.walisantri.test` di lingkungan
     * lokal membuktikannya secara langsung. Pagar ini tidak bergantung pada scope
     * cookie sama sekali.
     */
    private function sedangMencobaDemo(): bool
    {
        return (bool) session('magic_link_session');
    }

    public function showForm()
    {
        if (Auth::check() && ! $this->sedangMencobaDemo()) {
            return $this->redirectAuthenticated();
        }

        return view('auth.register', [
            'registrationOpen' => PlatformSetting::registrationOpen(),
            'demoOpen' => PlatformSetting::demoOpen(),
        ]);
    }

    public function store(Request $request, OnboardPesantren $onboard)
    {
        // Pendaftar yang datang dari demo harus benar-benar keluar dari sesi itu
        // sebelum tenant barunya dibuat — kalau tidak, ia mendaftar sambil masih
        // "login" sebagai wali pesantren contoh.
        if (Auth::check() && $this->sedangMencobaDemo()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if (Auth::check()) {
            return $this->redirectAuthenticated();
        }

        abort_if(! PlatformSetting::registrationOpen(), 404);

        $data = $request->validate([
            'nama_pesantren' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', new ValidTenantSlug, new SlugNotReserved, 'unique:pesantrens,slug'],
            'admin_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        try {
            $result = $onboard->execute(
                namaPesantren: $data['nama_pesantren'],
                slug: $data['slug'],
                adminName: $data['admin_name'],
                adminEmail: $data['email'],
                adminPassword: $data['password'],
            );
        } catch (QueryException $e) {
            Log::warning('register_onboard_failed', [
                'slug' => $data['slug'],
                'email' => $data['email'],
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'slug' => 'Pendaftaran gagal diproses — kemungkinan subdomain atau email sudah dipakai. Silakan periksa kembali lalu coba lagi.',
            ]);
        }

        $this->kirimEmailSambutan($result);

        // Sengaja TIDAK Auth::login() di sini: sesi yang lahir di apex tidak akan
        // pernah terbaca di host panel (cookie ber-scope host, §1.8). Sesinya
        // dipindahkan lewat tautan sekali pakai — lihat SerahTerimaSesiController.
        return redirect()->away(SerahTerimaSesiController::untuk($result['admin']));
    }

    private function redirectAuthenticated()
    {
        if (Auth::user()->role === 'wali_santri') {
            // Portal wali hidup di host pesantren (§1.8 Fase 1) dan konteks ini berjalan
            // di host platform — route('wali.dashboard') di sini akan gagal karena tidak
            // punya default slug. Bangun URL-nya dari tenant-nya sendiri.
            return redirect()->away(Auth::user()->urlPortalWali());
        }

        return redirect($this->adminUrl());
    }

    private function adminUrl(): string
    {
        return request()->getScheme().'://'.config('app.domain').'/admin';
    }

    /**
     * Dikirim di sini, bukan di dalam OnboardPesantren.
     *
     * Seluruh isi service itu dibungkus DB::transaction, dan email yang terlanjur
     * keluar tidak bisa ikut di-rollback — pesantren akan menerima ucapan selamat
     * datang untuk akun yang batal dibuat.
     *
     * @param  array{pesantren: Pesantren, admin: User}  $result
     */
    private function kirimEmailSambutan(array $result): void
    {
        if (! EmailSetting::get('email_sambutan_enabled')) {
            return;
        }

        if (blank($result['admin']->email)) {
            return;
        }

        Mail::to($result['admin']->email)->queue(
            new SambutanPendaftaran($result['pesantren'], $result['admin'])
        );
    }
}
