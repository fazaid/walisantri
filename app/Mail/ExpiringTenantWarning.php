<?php

namespace App\Mail;

use App\Models\Pesantren;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpiringTenantWarning extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pesantren $pesantren,
        public readonly int $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Langganan {$this->pesantren->nama_pesantren} akan berakhir dalam {$this->daysLeft} hari",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.expiring-tenant-warning');
    }
}
