<?php

namespace App\Jobs;

use App\Models\Pesantren;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WarnExpiringTenantsWhatsApp implements ShouldQueue
{
    use Queueable;

    // Pengecualian SEMPIT terhadap kebijakan "WA selalu manual" (§12) — khusus
    // reminder billing H-3 & H-1. Fitur WA lain (magic link, broadcast wali,
    // rapor, dsb) tetap manual dan TIDAK terpengaruh oleh perubahan ini.
    private const WARN_DAYS = [3, 1];

    public int $timeout = 300;

    // Job ini mengirim WA — jangan auto-retry supaya tidak berisiko kirim
    // notifikasi dobel ke admin pesantren.
    public int $tries = 1;

    public function handle(): void
    {
        // Kill-switch dari halaman Pengaturan WhatsApp Super Admin.
        if (! WhatsAppSetting::get('reminder_expired_enabled')) {
            return;
        }

        foreach (self::WARN_DAYS as $days) {
            $from = now()->addDays($days)->startOfDay();
            $to = now()->addDays($days)->endOfDay();

            Pesantren::whereIn('status_berlangganan', ['trial', 'active'])
                ->whereBetween('expired_at', [$from, $to])
                ->with('users')
                ->eachById(function (Pesantren $pesantren) use ($days) {
                    $admin = $pesantren->users
                        ->where('role', 'admin_pesantren')
                        ->first();

                    if (! $admin || blank($admin->phone_number)) {
                        return;
                    }

                    KirimNotifikasiWhatsapp::dispatch(
                        $admin->phone_number,
                        $this->buildMessage($pesantren, $days),
                    );
                });
        }
    }

    private function buildMessage(Pesantren $pesantren, int $daysLeft): string
    {
        $template = WhatsAppMessageTemplate::get('reminder_expired', self::DEFAULT_TEMPLATE);

        return strtr($template, [
            '{nama_pesantren}' => $pesantren->nama_pesantren,
            '{sisa_hari}' => (string) $daysLeft,
            '{tanggal_expired}' => $pesantren->expired_at->locale('id')->translatedFormat('d F Y'),
            '{link_billing}' => url('/admin/billing-page'),
        ]);
    }

    private const DEFAULT_TEMPLATE = <<<'TEXT'
    Assalamu'alaikum, Admin {nama_pesantren}.

    Langganan Walisantri.com Anda akan berakhir dalam {sisa_hari} hari (pada {tanggal_expired}).

    Segera perpanjang agar data santri dan akses portal wali tidak terganggu:
    {link_billing}

    Terima kasih.
    TEXT;
}
