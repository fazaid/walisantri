<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PurgeAuditLogs implements ShouldQueue
{
    use Queueable;

    // Retention §10.3: operasional 2 tahun, billing/paket 5 tahun
    private const OPERATIONAL_YEARS = 2;

    private const BILLING_YEARS = 5;

    private const BILLING_EVENTS = [
        'pesantren.paket_changed',
        'pesantren.activated',
        'pesantren.suspended',
    ];

    public int $timeout = 300;

    // Operasi delete idempoten — aman di-retry sekali kalau gagal transient.
    public int $tries = 2;

    public function handle(): void
    {
        ActivityLog::whereIn('event', self::BILLING_EVENTS)
            ->where('created_at', '<', now()->subYears(self::BILLING_YEARS))
            ->delete();

        ActivityLog::whereNotIn('event', self::BILLING_EVENTS)
            ->where('created_at', '<', now()->subYears(self::OPERATIONAL_YEARS))
            ->delete();
    }
}
