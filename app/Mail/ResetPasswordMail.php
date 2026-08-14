<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $token,
        public readonly string $email,
        public readonly string $nama,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Atur ulang kata sandi Walisantri.com');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.reset-password',
            with: [
                'url' => route('password.reset', ['token' => $this->token, 'email' => $this->email]),
                'menitBerlaku' => Config::get('auth.passwords.users.expire', 60),
            ],
        );
    }
}
