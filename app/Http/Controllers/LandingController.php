<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Services\PaketHargaService;
use App\Support\SandboxDemo;

class LandingController extends Controller
{
    public function __construct(private PaketHargaService $harga) {}

    /**
     * $sameDomain diteruskan dari routes/web.php: di lingkungan yang base_domain
     * dan app_domain-nya sama, '/' hanya boleh terdaftar sekali (lihat komentar
     * di routes/web.php), jadi route ini merangkap sebagai pintu masuk app.
     */
    public function __invoke(bool $sameDomain = false)
    {
        if ($sameDomain && auth()->check()) {
            return match (auth()->user()->role) {
                // Lihat catatan di WaliLoginController::redirectAfterLogin().
                'wali_santri' => redirect()->away(auth()->user()->urlPortalWali()),
                default => redirect('/admin'),
            };
        }

        return view('landing', [
            'registrationOpen' => PlatformSetting::registrationOpen(),
            'demoOpen' => PlatformSetting::demoOpen(),
            'demoWaliUrl' => SandboxDemo::waliUrl(),
            // Landing tidak lagi memajang paket sama sekali — seluruhnya di /harga
            // (§1.6). Yang tersisa cuma angka "mulai Rp ..." di jawaban FAQ, dan
            // itu pun turunan BillingSetting, bukan ditulis di Blade.
            'hargaTerendah' => $this->harga->hargaTerendah(),
        ]);
    }
}
