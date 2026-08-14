<?php

namespace App\Mail;

use App\Support\TautanVerifikasiEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifikasiEmail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $url,
        public readonly string $nama,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Konfirmasi alamat email Walisantri.com');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.verifikasi-email',
            with: ['menitBerlaku' => TautanVerifikasiEmail::MENIT_BERLAKU],
        );
    }
}
