<?php

namespace App\Http\Controllers;

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
            'paketList' => $this->harga->kartu(),
            'addOn' => $this->harga->addOnMaju(),
            ...$this->harga->konteksSiklus(),
        ]);
    }
}
