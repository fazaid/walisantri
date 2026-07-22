<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Filament\Resources\DemoRequests\DemoRequestResource;
use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\DemoRequest;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class DemoRequestObserver
{
    public function creating(DemoRequest $demoRequest): void
    {
        $match = DemoRequest::where(function ($query) use ($demoRequest) {
            $query->where('email', $demoRequest->email)
                ->orWhere('no_hp', $demoRequest->no_hp);
        })
            ->where('created_at', '>=', now()->subDays(30))
            ->latest('created_at')
            ->first();

        if ($match) {
            $demoRequest->duplicate_of_id = $match->id;
        }
    }

    public function created(DemoRequest $demoRequest): void
    {
        $this->notifySuperAdmins($demoRequest);
        $this->sendTerimaKasihWhatsapp($demoRequest);
    }

    private function notifySuperAdmins(DemoRequest $demoRequest): void
    {
        $superAdmins = User::where('role', UserRole::SuperAdmin->value)->get();

        if ($superAdmins->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Lead demo baru: '.$demoRequest->nama_pesantren)
            ->body(trim("{$demoRequest->kota} • {$demoRequest->no_hp}", ' •'))
            ->icon('heroicon-o-sparkles')
            ->actions([
                Action::make('view')
                    ->label('Lihat')
                    ->url(DemoRequestResource::getUrl('view', ['record' => $demoRequest]))
                    ->markAsRead(),
            ])
            ->sendToDatabase($superAdmins);
    }

    // Pengecualian SEMPIT ke-4 atas kebijakan "WA selalu manual" (PRD §12) —
    // ucapan terima kasih + link grup support otomatis ke pendaftar demo.
    private function sendTerimaKasihWhatsapp(DemoRequest $demoRequest): void
    {
        // Kill-switch dari halaman Pengaturan WhatsApp Super Admin.
        if (! WhatsAppSetting::get('notif_demo_terima_kasih_enabled')) {
            return;
        }

        if (blank($demoRequest->no_hp)) {
            return;
        }

        KirimNotifikasiWhatsapp::dispatch(
            $demoRequest->no_hp,
            $this->buildDemoMessage($demoRequest),
        );
    }

    private function buildDemoMessage(DemoRequest $demoRequest): string
    {
        $template = WhatsAppMessageTemplate::get('notif_demo_terima_kasih', self::DEFAULT_TEMPLATE);

        return strtr($template, [
            '{nama_kontak}' => $demoRequest->nama_kontak,
            '{nama_pesantren}' => $demoRequest->nama_pesantren,
        ]);
    }

    private const DEFAULT_TEMPLATE = <<<'TEXT'
    Assalamu'alaikum, {nama_kontak}.

    Terima kasih sudah mendaftar demo Walisantri.com untuk {nama_pesantren}. 🙏

    Tim kami akan segera menghubungi Anda. Sambil menunggu, silakan gabung grup WhatsApp support kami untuk tanya-jawab & bantuan:
    https://chat.whatsapp.com/XXXXXXXXXXXXXXX

    Terima kasih.
    TEXT;
}
