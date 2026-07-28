<?php

namespace App\Jobs;

use App\Mail\ExpiringTenantWarning;
use App\Models\Pesantren;
use App\Support\Waktu;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class WarnExpiringTenants implements ShouldQueue
{
    use Queueable;

    // Kirim peringatan pada H-7 dan H-3 sebelum expired (§11)
    private const WARN_DAYS = [7, 3];

    public int $timeout = 300;

    // Job ini mengirim email — jangan auto-retry supaya tidak berisiko kirim
    // notifikasi dobel ke admin pesantren.
    public int $tries = 1;

    public function handle(): void
    {
        foreach (self::WARN_DAYS as $days) {
            // Batas hari mengikuti kalender WIB lalu dikonversi ke UTC — kalau
            // dihitung langsung di UTC, jendela H-$days bergeser 7 jam sehingga
            // pesantren yang expired dini hari WIB masuk bucket hari sebelumnya.
            $patokan = Waktu::sekarang()->addDays($days);
            $from = Waktu::awalHari($patokan);
            $to = Waktu::akhirHari($patokan);

            Pesantren::whereIn('status_berlangganan', ['trial', 'active'])
                ->whereBetween('expired_at', [$from, $to])
                ->with('users')
                ->eachById(function (Pesantren $pesantren) use ($days) {
                    $admin = $pesantren->users
                        ->where('role', 'admin_pesantren')
                        ->first();

                    if ($admin) {
                        Mail::to($admin->email)->queue(
                            new ExpiringTenantWarning($pesantren, $days)
                        );
                    }
                });
        }
    }
}
