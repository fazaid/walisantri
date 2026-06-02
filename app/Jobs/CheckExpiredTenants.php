<?php

namespace App\Jobs;

use App\Models\Pesantren;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckExpiredTenants implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Tandai expired semua tenant yang expired_at sudah lewat
        // tapi status masih trial/active — jalan setiap 00.01 (§11)
        Pesantren::whereIn('status_berlangganan', ['trial', 'active'])
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now())
            ->eachById(function (Pesantren $pesantren) {
                $pesantren->update(['status_berlangganan' => 'expired']);
            });
    }
}
