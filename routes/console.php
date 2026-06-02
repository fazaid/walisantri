<?php

use Illuminate\Support\Facades\Schedule;

// PRD §11 — Semua scheduled task terdaftar di sini.
// Notifikasi WhatsApp ke wali TIDAK dijadwalkan — selalu manual via Filament.

// Harian 00.01 — Update status_berlangganan dari expired_at
Schedule::job(\App\Jobs\CheckExpiredTenants::class)
    ->dailyAt('00:01')
    ->withoutOverlapping();

// Harian 09.00 — Email peringatan 7 & 3 hari sebelum expired
Schedule::job(\App\Jobs\WarnExpiringTenants::class)
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Tanggal 1 tiap bulan — Purge audit logs sesuai retention (§10.3)
Schedule::job(\App\Jobs\PurgeAuditLogs::class)
    ->monthlyOn(1, '03:30')
    ->withoutOverlapping();

// Harian 02.00 — pg_dump -Fc → gzip → R2 walisantri-backup/daily/ (§6.2)
Schedule::job(\App\Jobs\DatabaseBackup::class)
    ->dailyAt('02:00')
    ->withoutOverlapping();

// Tiap 25 menit — Pre-generate cache dashboard wali santri aktif (§4.5)
Schedule::job(\App\Jobs\WarmDashboardCache::class)
    ->cron('*/25 * * * *')
    ->withoutOverlapping();

// Harian 03.00 — Hapus cache Redis santri non-aktif
Schedule::job(\App\Jobs\PruneStaleCache::class)
    ->dailyAt('03:00')
    ->withoutOverlapping();
