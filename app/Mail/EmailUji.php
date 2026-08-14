<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Email percobaan dari halaman Pengaturan Email (§12.2).
 *
 * Sengaja TIDAK implements ShouldQueue — tujuannya memverifikasi kredensial
 * SMTP sekarang juga, jadi kegagalannya harus muncul di layar super admin,
 * bukan mengendap di failed_jobs beberapa detik kemudian.
 */
class EmailUji extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Uji koneksi email Walisantri.com');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.uji');
    }
}
