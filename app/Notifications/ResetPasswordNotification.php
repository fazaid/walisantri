<?php

namespace App\Notifications;

use App\Mail\ResetPasswordMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

/**
 * Notification pertama di repo ini (§9.1).
 *
 * Menggantikan `ResetPassword` bawaan Laravel yang subjek & isinya berbahasa
 * Inggris dan memakai template `x-mail::message` yang tidak dipublish di sini —
 * seluruh email platform memakai layout sendiri (§12.2).
 */
class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return (new ResetPasswordMail(
            token: $this->token,
            email: $notifiable->getEmailForPasswordReset(),
            nama: $notifiable->name,
        ))->to($notifiable->getEmailForPasswordReset());
    }
}
