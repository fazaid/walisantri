<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pagar host tenant (§1.8 Fase 1) — dipasang pada grup rute {slug}.walisantri.com.
 *
 * Dua tugas, dan keduanya harus jalan di SETIAP request:
 *
 * 1. Menyetel default parameter `slug` supaya 38 call site `route('wali.*')` tetap
 *    bekerja tanpa disentuh setelah rutenya pindah ke grup domain berparameter.
 *
 * 2. Menolak sesi tenant lain. Ini sengaja middleware, BUKAN pengecekan di
 *    WaliLoginController: sesi yang SUDAH ada ikut terbawa ke request berikutnya
 *    tanpa pernah menyentuh form login, sehingga cek di controller login tidak
 *    pernah dilewati. Pemeriksaannya per request, bukan sekali saat masuk.
 *
 * Dengan cookie ber-scope host (SESSION_DOMAIN tidak disetel — lihat §1.8), sesi
 * memang tidak menyeberang antar-host, jadi pagar ini lapis kedua. Ia tetap
 * dipasang karena satu jalur masih terbuka: seseorang bisa mengetik kredensial
 * tenant B di form login host tenant A — email unik global, jadi autentikasinya
 * berhasil. Di situlah pagar ini menggigit.
 */
class TenantHost
{
    public function handle(Request $request, Closure $next): Response
    {
        // Diisi PublicTenantResolver (hostname → tenant_domains). Middleware itu
        // sudah abort(404) untuk host tak dikenal, jadi di sini nilainya pasti ada
        // kecuali urutan middleware-nya salah — dan itu layak gagal keras.
        $pesantren = $request->attributes->get('public_pesantren');

        abort_if($pesantren === null, 404);

        URL::defaults(['slug' => $pesantren->slug]);

        // Parameter domain ikut dikirim ke controller sebagai argumen PERTAMA —
        // `PresensiController::show(int $santriId)` akan menerima slug, bukan id.
        // Dilupakan di sini supaya ke-17 controller wali tidak perlu menumbuhkan
        // parameter yang tidak mereka pedulikan. Default di atas sudah dipasang,
        // jadi pembangkitan URL tetap membawa slug-nya.
        $request->route()?->forgetParameter('slug');

        $user = $request->user();

        if ($user !== null && (int) $user->pesantren_id !== (int) $pesantren->id) {
            abort(403, 'Akun ini bukan milik pesantren yang beralamat di sini.');
        }

        return $next($request);
    }
}
