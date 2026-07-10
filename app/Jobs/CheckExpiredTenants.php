<?php

namespace App\Jobs;

use App\Models\Pesantren;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckExpiredTenants implements ShouldQueue
{
    use Queueable;

    // Grace period sebelum suspend (harus sama dengan SaaSLifecycleLock::WALI_GRACE_DAYS)
    private const GRACE_DAYS = 7;

    public int $timeout = 300;

    // Job ini mengirim notifikasi WA saat expired — jangan auto-retry supaya
    // tidak berisiko kirim notifikasi dobel ke admin pesantren.
    public int $tries = 1;

    public function handle(): void
    {
        // 1. trial/active → expired saat expired_at terlewat
        Pesantren::whereIn('status_berlangganan', ['trial', 'active'])
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now())
            ->with('users')
            ->eachById(function (Pesantren $pesantren) {
                $pesantren->update(['status_berlangganan' => 'expired']);
                $this->notifyExpired($pesantren);
            });

        // 2. expired → suspended setelah grace period 7 hari
        Pesantren::where('status_berlangganan', 'expired')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now()->subDays(self::GRACE_DAYS))
            ->eachById(function (Pesantren $pesantren) {
                $pesantren->update(['status_berlangganan' => 'suspended']);
            });
    }

    // Pengecualian SEMPIT kedua terhadap kebijakan "WA selalu manual" (PRD §12) —
    // khusus notifikasi sekali saat status baru saja bertransisi ke expired,
    // di samping reminder H-3/H-1 (WarnExpiringTenantsWhatsApp).
    private function notifyExpired(Pesantren $pesantren): void
    {
        if (! WhatsAppSetting::get('notif_trial_habis_enabled')) {
            return;
        }

        $admin = $pesantren->users
            ->where('role', 'admin_pesantren')
            ->first();

        if (! $admin || blank($admin->phone_number)) {
            return;
        }

        KirimNotifikasiWhatsapp::dispatch(
            $admin->phone_number,
            $this->buildMessage($pesantren),
        );
    }

    private function buildMessage(Pesantren $pesantren): string
    {
        $template = WhatsAppMessageTemplate::get('notif_trial_habis', self::DEFAULT_TEMPLATE);

        return strtr($template, [
            '{nama_pesantren}' => $pesantren->nama_pesantren,
            '{tanggal_expired}' => $pesantren->expired_at->locale('id')->translatedFormat('d F Y'),
            '{link_billing}' => url('/admin/billing-page'),
        ]);
    }

    private const DEFAULT_TEMPLATE = <<<'TEXT'
    Assalamu'alaikum, Admin {nama_pesantren}.

    Masa langganan Walisantri.com Anda telah berakhir pada {tanggal_expired}.

    Akses admin/ustadz sudah dikunci dan portal wali santri masuk masa tenggang 7 hari (read-only). Segera perpanjang agar tidak terganggu:
    {link_billing}

    Terima kasih.
    TEXT;
}
