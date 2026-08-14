<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class PembayaranDiterima extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly Order $order,
        public readonly Carbon $expiredAtBaru,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pembayaran diterima — {$this->order->nomor_order}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.pembayaran-diterima');
    }
}
