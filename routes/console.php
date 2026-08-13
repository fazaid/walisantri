<?php

use App\Jobs\CheckExpiredTenants;
use App\Jobs\PruneStaleCache;
use App\Jobs\PurgeAuditLogs;
use App\Jobs\WarmDashboardCache;
use App\Jobs\WarnExpiringTenants;
use App\Jobs\WarnExpiringTenantsWhatsApp;
use Illuminate\Support\Facades\Schedule;

// PRD §11 — Semua scheduled task terdaftar di sini.
// Notifikasi WhatsApp secara umum TIDAK dijadwalkan — selalu manual via Filament (§12).
// PENGECUALIAN SEMPIT: reminder billing H-3/H-1 (WarnExpiringTenantsWhatsApp) di bawah
// ini dijadwalkan otomatis sebagai channel tambahan selain email. Fitur WA lain (magic
// link, broadcast wali, rapor, dsb) TIDAK terpengaruh dan tetap manual sepenuhnya.
//
// Semua jam di bawah ini WIB, bukan UTC — aplikasi berjalan di UTC
// (config/app.php), jadi tiap task wajib ->timezone(config('app.display_timezone'))
// supaya "09.00" benar-benar pagi bagi pesantren, bukan 16.00 WIB.

// Harian 00.01 — Update status_berlangganan dari expired_at
Schedule::job(CheckExpiredTenants::class)
    ->dailyAt('00:01')
    ->timezone(config('app.display_timezone'))
    ->withoutOverlapping();

// Harian 09.00 — Email peringatan 7 & 3 hari sebelum expired
Schedule::job(WarnExpiringTenants::class)
    ->dailyAt('09:00')
    ->timezone(config('app.display_timezone'))
    ->withoutOverlapping();

// Harian 09.05 — WhatsApp peringatan 3 & 1 hari sebelum expired (channel tambahan,
// pengecualian sempit atas kebijakan WA manual — lihat komentar di atas & PRD §12)
Schedule::job(WarnExpiringTenantsWhatsApp::class)
    ->dailyAt('09:05')
    ->timezone(config('app.display_timezone'))
    ->withoutOverlapping();

// Tanggal 1 tiap bulan — Purge audit logs sesuai retention (§10.3)
Schedule::job(PurgeAuditLogs::class)
    ->monthlyOn(1, '03:30')
    ->timezone(config('app.display_timezone'))
    ->withoutOverlapping();

// Backup DB harian 02.00 TIDAK lagi dijadwalkan di sini — ditangani oleh
// cron OS langsung ke scripts/backup.sh (pg_dump + file + offsite rclone).
// Lihat docs/backup-restore.md. Job Laravel lama dihapus karena menulis ke
// disk 'r2-backup' yang tidak pernah dikonfigurasi (selalu error diam-diam).

// Tiap 25 menit — Pre-generate cache dashboard wali santri aktif (§4.5)
Schedule::job(WarmDashboardCache::class)
    ->cron('*/25 * * * *')
    ->withoutOverlapping();

// Harian 03.00 — Hapus cache Redis santri non-aktif
Schedule::job(PruneStaleCache::class)
    ->dailyAt('03:00')
    ->timezone(config('app.display_timezone'))
    ->withoutOverlapping();
