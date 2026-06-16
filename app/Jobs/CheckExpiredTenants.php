<?php

namespace App\Jobs;

use App\Models\Pesantren;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckExpiredTenants implements ShouldQueue
{
    use Queueable;

    // Grace period sebelum suspend (harus sama dengan SaaSLifecycleLock::WALI_GRACE_DAYS)
    private const GRACE_DAYS = 7;

    public function handle(): void
    {
        // 1. trial/active → expired saat expired_at terlewat
        Pesantren::whereIn('status_berlangganan', ['trial', 'active'])
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now())
            ->eachById(function (Pesantren $pesantren) {
                $pesantren->update(['status_berlangganan' => 'expired']);
            });

        // 2. expired → suspended setelah grace period 7 hari
        Pesantren::where('status_berlangganan', 'expired')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now()->subDays(self::GRACE_DAYS))
            ->eachById(function (Pesantren $pesantren) {
                $pesantren->update(['status_berlangganan' => 'suspended']);
            });
    }
}
