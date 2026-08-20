<?php

namespace App\Http\Middleware;

use App\Enums\Modul;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menutup rute portal wali yang modulnya dimatikan pesantren.
 *
 * Dipakai deklaratif di routes/web.php (`->middleware('modul:keuangan')`), sejajar
 * dengan `signed`, `throttle:*`, dan `magic.token` yang sudah tertulis di sana —
 * kebijakan rute dibaca di baris rutenya sendiri, bukan dikejar ke dalam controller.
 * Ia juga menutup POST (spp.konfirmasi, izin.store), yang tidak bisa dijaga lapis view.
 *
 * ⚠️ 404, BUKAN 403. Bagi orang tua santri, modul yang pesantrennya tidak pakai
 * memang tidak ada — 403 justru mengumumkan keberadaan sesuatu yang sedang ditolak,
 * dan mengundang pertanyaan ke pengasuh tentang fitur yang bukan haknya untuk minta.
 * Ini berbeda dari panel admin, yang sengaja 403: di sana yang membuka URL adalah
 * orang yang berwenang menyalakannya kembali.
 */
class PastikanModulAktif
{
    public function handle(Request $request, Closure $next, string $modul): Response
    {
        abort_unless(Modul::from($modul)->aktif(), 404);

        return $next($request);
    }
}
