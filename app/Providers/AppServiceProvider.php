<?php

// File: app/Providers/AppServiceProvider.php
// Replace seluruh isi file dengan kode ini.

namespace App\Providers;

use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use App\Observers\PesantrenObserver;
use App\Observers\SantriObserver;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        $this->registerModuleGates();
        $this->registerQueueRouting();
        $this->registerRateLimiters();
        $this->registerObservers();
    }

    // -----------------------------------------------------------------
    // Laravel Gate — lock modul berdasarkan paket langganan (PRD §5.1)
    // -----------------------------------------------------------------
    private function registerModuleGates(): void
    {
        // Modul Kesehatan — Berkembang ke atas (§5.1)
        Gate::define('access-modul-kesehatan', function ($user) {
            if ($user->isSuperAdmin()) return true;

            return in_array($user->pesantren?->paket_langganan, [
                'berkembang',
                'maju',
            ]);
        });

        // Modul Inventaris — Maju saja (§5.1)
        Gate::define('access-modul-inventaris', function ($user) {
            if ($user->isSuperAdmin()) return true;

            return $user->pesantren?->paket_langganan === 'maju';
        });

        // Modul AI — Maju saja, post v1.0 (§5.1)
        Gate::define('access-modul-ai', function ($user) {
            if ($user->isSuperAdmin()) return true;

            return $user->pesantren?->paket_langganan === 'maju';
        });

        // Modul Akademik + Mutaba'ah — semua paket termasuk Gratis (§5.1)
        Gate::define('access-modul-akademik', function ($user) {
            return ! is_null($user->pesantren?->paket_langganan);
        });

        // Akses billing — hanya admin pesantren & super admin
        Gate::define('access-billing', function ($user) {
            return $user->isSuperAdmin() || $user->isAdminPesantren();
        });
    }

    // -----------------------------------------------------------------
    // Observers — audit log events (PRD §10.2)
    // -----------------------------------------------------------------
    private function registerObservers(): void
    {
        Santri::observe(SantriObserver::class);
        User::observe(UserObserver::class);
        Pesantren::observe(PesantrenObserver::class);
    }

    // -----------------------------------------------------------------
    // Rate Limiters (PRD §9.2)
    // -----------------------------------------------------------------
    private function registerRateLimiters(): void
    {
        RateLimiter::for('check-slug', fn ($request) =>
            Limit::perMinute(30)->by($request->ip())->response(fn () =>
                response()->json(['available' => false, 'message' => 'Terlalu banyak permintaan.'], 429)
            )
        );
    }

    // -----------------------------------------------------------------
    // Queue Routing terpusat (Laravel 13 — PRD §4.5)
    // -----------------------------------------------------------------
    private function registerQueueRouting(): void
    {
        // Cek apakah class job sudah ada sebelum route didaftarkan
        // (mencegah error saat job belum dibuat di fase ini)

        if (class_exists(\App\Jobs\KirimNotifikasiWhatsapp::class)) {
            Queue::route(
                \App\Jobs\KirimNotifikasiWhatsapp::class,
                connection: 'redis',
                queue: 'whatsapp-notif'
            );
        }

        if (class_exists(\App\Jobs\ProsesImporSantri::class)) {
            Queue::route(
                \App\Jobs\ProsesImporSantri::class,
                connection: 'redis',
                queue: 'bulk-import'
            );
        }

        if (class_exists(\App\Jobs\KalkulasiRaporTahfidz::class)) {
            Queue::route(
                \App\Jobs\KalkulasiRaporTahfidz::class,
                connection: 'redis',
                queue: 'rapor-calc'
            );
        }
    }
}