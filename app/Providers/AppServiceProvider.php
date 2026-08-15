<?php

// File: app/Providers/AppServiceProvider.php
// Replace seluruh isi file dengan kode ini.

namespace App\Providers;

use App\Models\DemoRequest;
use App\Models\EmailGatewaySetting;
use App\Models\Kelas;
use App\Models\KesantrianKesehatan;
use App\Models\MasterPengumuman;
use App\Models\Pesantren;
use App\Models\PlatformBankAccount;
use App\Models\Santri;
use App\Models\User;
use App\Observers\DemoRequestObserver;
use App\Observers\KelasObserver;
use App\Observers\KesantrianKesehatanObserver;
use App\Observers\MasterPengumumanObserver;
use App\Observers\PesantrenObserver;
use App\Observers\PlatformBankAccountObserver;
use App\Observers\SantriObserver;
use App\Observers\UserObserver;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadMigrationsFrom([
            database_path('migrations/central'),
            database_path('migrations/tenant'),
        ]);
    }

    public function boot(): void
    {
        // Waktu disimpan UTC, tapi ditampilkan WIB — tanpa ini seluruh kolom
        // & entry datetime Filament jatuh ke config('app.timezone') alias UTC,
        // sehingga terlihat mundur 7 jam bagi pengguna.
        FilamentTimezone::set(config('app.display_timezone'));

        // Kredensial SMTP disimpan di database, bukan .env (§3.1) — disuntikkan
        // ke config di sini. Aman dipanggil sebelum tabelnya ada: method-nya
        // menelan kegagalan dan membiarkan nilai .env yang berlaku.
        EmailGatewaySetting::applyToConfig();

        // ...dan disegarkan lagi sebelum SETIAP job antrean.
        //
        // boot() hanya jalan sekali per proses. Permintaan web berumur pendek jadi
        // selalu membaca nilai terbaru, tapi worker Supervisor hidup berhari-hari:
        // ia memegang config dari saat ia dinyalakan dan tidak pernah melihat
        // perubahan yang dibuat super admin sesudahnya.
        //
        // Akibatnya nyata dan membingungkan: tombol "Kirim Email Uji" (sinkron,
        // di dalam request) memakai alamat pengirim baru dan terlihat benar,
        // sementara email sambutan (antre, dikerjakan worker lama) tetap keluar
        // dengan alamat lama tanpa Reply-To. Persis itu yang terjadi 2026-08-14.
        //
        // Murah: nilainya di-cache satu jam dalam satu key, dan `set()` sudah
        // menghapus cache itu — worker berbagi cache store yang sama, jadi
        // perubahan langsung terlihat tanpa perlu restart.
        Queue::before(fn () => EmailGatewaySetting::applyToConfig());

        $this->registerRateLimiters();
        $this->registerObservers();
    }

    // -----------------------------------------------------------------
    // Observers — audit log events (PRD §10.2)
    // -----------------------------------------------------------------
    private function registerObservers(): void
    {
        Santri::observe(SantriObserver::class);
        User::observe(UserObserver::class);
        Pesantren::observe(PesantrenObserver::class);
        PlatformBankAccount::observe(PlatformBankAccountObserver::class);
        MasterPengumuman::observe(MasterPengumumanObserver::class);
        DemoRequest::observe(DemoRequestObserver::class);
        Kelas::observe(KelasObserver::class);
        KesantrianKesehatan::observe(KesantrianKesehatanObserver::class);
    }

    // -----------------------------------------------------------------
    // Rate Limiters (PRD §9.2)
    // -----------------------------------------------------------------
    private function registerRateLimiters(): void
    {
        RateLimiter::for('check-slug', fn ($request) => Limit::perMinute(30)->by($request->ip())->response(fn () => response()->json(['available' => false, 'message' => 'Terlalu banyak permintaan.'], 429)
        )
        );

        RateLimiter::for('register', fn ($request) => Limit::perHour(5)->by($request->ip())
        );

        RateLimiter::for('demo', fn ($request) => Limit::perHour(5)->by($request->ip())
        );

        // Dikunci ke email+IP, bukan IP saja, dengan alasan yang sama seperti login:
        // satu pesantren di balik satu IP publik tidak boleh saling mengunci.
        // Melengkapi throttle broker (config/auth.php) yang hanya menahan
        // permintaan berulang per alamat, bukan penyisiran banyak alamat dari satu IP.
        RateLimiter::for('password-reset', fn ($request) => Limit::perHour(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip())
        );

        // Menahan dua hal sekaligus: penyalahgunaan tombol "kirim ulang" yang bisa
        // menghabiskan kuota harian Brevo, dan penebakan tanda tangan pada tautan
        // verifikasi. Dikunci ke user bila ada sesi, selebihnya ke IP.
        RateLimiter::for('verifikasi-email', fn ($request) => Limit::perHour(6)
            ->by($request->user()?->id ?: $request->ip())
        );
    }
}
