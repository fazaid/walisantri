<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoicePdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceDibuat extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly Order $order,
        public readonly Invoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->invoice->nomor_invoice} — menunggu pembayaran",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invoice-dibuat');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => app(InvoicePdf::class)->untuk($this->order, $this->invoice)->output(),
                "Invoice-{$this->invoice->nomor_invoice}.pdf",
            )->withMime('application/pdf'),
        ];
    }
}
