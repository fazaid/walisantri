<?php

namespace App\Jobs;

use App\Models\Pesantren;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class WarnExpiringTenants implements ShouldQueue
{
    use Queueable;

    // Kirim peringatan pada H-7 dan H-3 sebelum expired (§11)
    private const WARN_DAYS = [7, 3];

    public function handle(): void
    {
        foreach (self::WARN_DAYS as $days) {
            $from = now()->addDays($days)->startOfDay();
            $to   = now()->addDays($days)->endOfDay();

            Pesantren::whereIn('status_berlangganan', ['trial', 'active'])
                ->whereBetween('expired_at', [$from, $to])
                ->with('users')
                ->eachById(function (Pesantren $pesantren) use ($days) {
                    $admin = $pesantren->users
                        ->where('role', 'admin_pesantren')
                        ->first();

                    if ($admin) {
                        Mail::to($admin->email)->queue(
                            new \App\Mail\ExpiringTenantWarning($pesantren, $days)
                        );
                    }
                });
        }
    }
}
