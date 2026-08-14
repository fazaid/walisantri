<?php

namespace App\Mail;

use App\Models\Pesantren;
use App\Models\User;
use App\Support\TautanVerifikasiEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SambutanPendaftaran extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // Email lebih baik hilang daripada dobel — alasan yang sama dipakai
    // WarnExpiringTenants sejak awal.
    public int $tries = 1;

    public function __construct(
        public readonly Pesantren $pesantren,
        public readonly User $admin,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Selamat datang di Walisantri.com, {$this->pesantren->nama_pesantren}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.sambutan',
            with: [
                // Tautan verifikasi menumpang di email sambutan, bukan dikirim
                // sebagai email kedua — dua pesan beruntun saat mendaftar hanya
                // menaikkan peluang keduanya diabaikan.
                'urlVerifikasi' => TautanVerifikasiEmail::untuk($this->admin),
            ],
        );
    }
}
