<?php

namespace App\Jobs;

use App\Enums\StatusBerlangganan;
use App\Mail\ExpiringTenantWarning;
use App\Models\EmailSetting;
use App\Models\Pesantren;
use App\Support\PenerimaEmail;
use App\Support\Waktu;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
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
        // Kill-switch dari halaman Pengaturan Email Super Admin (§12.2).
        if (! EmailSetting::get('email_reminder_expired_enabled')) {
            return;
        }

        foreach (self::WARN_DAYS as $days) {
            // Batas hari mengikuti kalender WIB lalu dikonversi ke UTC — kalau
            // dihitung langsung di UTC, jendela H-$days bergeser 7 jam sehingga
            // pesantren yang expired dini hari WIB masuk bucket hari sebelumnya.
            $patokan = Waktu::sekarang()->addDays($days);
            $from = Waktu::awalHari($patokan);
            $to = Waktu::akhirHari($patokan);

            Pesantren::whereIn('status_berlangganan', StatusBerlangganan::berjalan())
                ->whereBetween('expired_at', [$from, $to])
                ->with('users')
                ->eachById(function (Pesantren $pesantren) use ($days) {
                    $admin = PenerimaEmail::adminPesantren($pesantren);

                    // Tanpa penjagaan ini Mail::to(null) melempar exception —
                    // users.email nullable sejak central/2026_07_09_100001.
                    if (! $admin) {
                        return;
                    }

                    if (! $this->tandaiTerkirim($pesantren, $days)) {
                        return;
                    }

                    Mail::to($admin->email)->queue(
                        new ExpiringTenantWarning($pesantren, $days)
                    );
                });
        }
    }

    /**
     * Penanda sekali-sehari per pesantren per ambang.
     *
     * `withoutOverlapping()` di scheduler hanya mencegah dua eksekusi yang
     * bertumpang tindih, bukan dua eksekusi berurutan — `schedule:run` yang
     * kebetulan dipicu ulang (mis. saat deploy) akan mengirim email kedua ke
     * admin yang sama. Kunci cache dibuang sendiri setelah dua hari.
     *
     * @return bool true bila ini pengiriman pertama hari ini
     */
    private function tandaiTerkirim(Pesantren $pesantren, int $days): bool
    {
        $tanggal = Waktu::sekarang()->toDateString();

        return Cache::add("peringatan_expired:{$pesantren->id}:{$days}:{$tanggal}", true, now()->addDays(2));
    }
}
