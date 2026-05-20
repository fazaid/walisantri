<?php

// File: app/Providers/AppServiceProvider.php
// Replace seluruh isi file dengan kode ini.

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerModuleGates();
        $this->registerQueueRouting();
    }

    // -----------------------------------------------------------------
    // Laravel Gate — lock modul berdasarkan paket langganan (PRD §5.1)
    // -----------------------------------------------------------------
    private function registerModuleGates(): void
    {
        // Modul Kesehatan — Berkembang ke atas
        Gate::define('access-modul-kesehatan', function ($user) {
            if ($user->isSuperAdmin()) return true;

            return in_array($user->pesantren?->paket_langganan, [
                'berkembang',
                'akselerasi',
                'besar',
            ]);
        });

        // Modul Inventaris — Akselerasi ke atas
        Gate::define('access-modul-inventaris', function ($user) {
            if ($user->isSuperAdmin()) return true;

            return in_array($user->pesantren?->paket_langganan, [
                'akselerasi',
                'besar',
            ]);
        });

        // Modul AI (Laravel AI SDK) — Akselerasi ke atas (PRD §6)
        Gate::define('access-modul-ai', function ($user) {
            if ($user->isSuperAdmin()) return true;

            return in_array($user->pesantren?->paket_langganan, [
                'akselerasi',
                'besar',
            ]);
        });

        // Modul Akademik + Mutaba'ah — semua paket
        Gate::define('access-modul-akademik', function ($user) {
            return ! is_null($user->pesantren?->paket_langganan);
        });

        // Akses billing — hanya admin pesantren & super admin
        Gate::define('access-billing', function ($user) {
            return $user->isSuperAdmin() || $user->isAdminPesantren();
        });
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