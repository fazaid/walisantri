<?php

namespace App\Http\Controllers;

use App\Models\PlatformBrandingSetting;
use App\Models\PlatformContactSetting;
use App\Models\PlatformSetting;
use App\Services\PaketHargaService;

/**
 * /harga — rumah kanonik paket harga sejak §1.6. Landing hanya meringkasnya.
 *
 * Pola datanya sama dengan PanduanController: nav & footer di halaman ini partial
 * yang sama dengan landing, jadi ia butuh $registrationOpen/$demoOpen — tanpa itu
 * menutup pendaftaran menyisakan pintu yang masih terbuka di halaman ini.
 */
class HargaController extends Controller
{
    public function __construct(private PaketHargaService $harga) {}

    public function __invoke()
    {
        return view('harga', [
            'registrationOpen' => PlatformSetting::registrationOpen(),
            'demoOpen' => PlatformSetting::demoOpen(),
            // Tautan seksi di nav (#fitur, #cara-kerja, #faq) menunjuk landing,
            // bukan halaman ini: kalau relatif ia menggantung di sini dan tidak
            // membawa pembaca ke mana pun.
            'anchorBase' => route('landing'),
            // Paket Maju tidak didaftar sendiri — CTA-nya membuka WhatsApp tim.
            // Sumber utamanya setelan "Kontak Dukungan" (Merek & Kontak), dengan
            // nomor CS sebagai cadangan: hanya yang kedua punya nilai bawaan dari
            // migrasi, jadi tanpa cadangan itu kartu Maju kehilangan CTA-nya di
            // instalasi yang belum pernah mengisi wa_dukungan.
            'waDukungan' => PlatformBrandingSetting::waDukungan() ?? PlatformContactSetting::csWhatsapp(),
            'paketList' => $this->harga->kartu(),
            'addOn' => $this->harga->addOnMaju(),
            ...$this->harga->konteksSiklus(),
        ]);
    }
}
