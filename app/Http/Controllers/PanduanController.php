<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;

/**
 * /panduan memakai nav & footer yang sama dengan landing (partials/situs-*),
 * jadi ia butuh data yang sama pula. Dulu rutenya `Route::view` tanpa controller —
 * akibatnya tautan Demo di halaman ini tidak pernah ikut gerbang `demo_open`.
 */
class PanduanController extends Controller
{
    public function __invoke()
    {
        return view('panduan', [
            'registrationOpen' => PlatformSetting::registrationOpen(),
            'demoOpen' => PlatformSetting::demoOpen(),
            // Tautan seksi di nav menunjuk landing, bukan halaman ini: dari
            // /panduan ia harus absolut, kalau relatif "#harga" menggantung di
            // halaman panduan dan tidak membawa pembaca ke mana-mana.
            'anchorBase' => route('landing'),
        ]);
    }
}
