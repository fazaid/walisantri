<?php

namespace App\Notifications;

use App\Mail\VerifikasiEmail;
use App\Support\TautanVerifikasiEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class VerifikasiEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return (new VerifikasiEmail(
            url: TautanVerifikasiEmail::untuk($notifiable),
            nama: $notifiable->name,
        ))->to($notifiable->getEmailForVerification());
    }
}
