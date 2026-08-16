<?php

namespace App\Http\Controllers;

use App\Enums\DurasiLangganan;
use App\Enums\PaketLangganan;
use App\Models\BillingSetting;
use App\Models\PlatformSetting;
use App\Services\BillingCalculatorService;

class LandingController extends Controller
{
    public function __construct(private BillingCalculatorService $kalkulator) {}

    /**
     * $sameDomain diteruskan dari routes/web.php: di lingkungan yang base_domain
     * dan app_domain-nya sama, '/' hanya boleh terdaftar sekali (lihat komentar
     * di routes/web.php), jadi route ini merangkap sebagai pintu masuk app.
     */
    public function __invoke(bool $sameDomain = false)
    {
        if ($sameDomain && auth()->check()) {
            return match (auth()->user()->role) {
                'wali_santri' => redirect()->route('wali.dashboard'),
                default => redirect('/admin'),
            };
        }

        return view('landing', [
            'registrationOpen' => PlatformSetting::registrationOpen(),
            'demoOpen' => PlatformSetting::demoOpen(),
            'trialDays' => BillingSetting::get('trial_days', 14),
            'paketList' => $this->paketList(),
            'bonusEnam' => DurasiLangganan::EnamBulan->bonusBulan(),
            'bonusTahunan' => DurasiLangganan::DuabelasBulan->bonusBulan(),
        ]);
    }

    /**
     * Kartu harga landing. Angkanya WAJIB lewat BillingCalculatorService supaya
     * mengikuti BillingSetting — harga yang ditulis langsung di Blade akan diam-diam
     * menyimpang begitu super admin mengubahnya di BillingSettingsPage.
     */
    private function paketList(): array
    {
        return collect(PaketLangganan::cases())
            ->map(function (PaketLangganan $paket) {
                $hitung = $this->kalkulator->hitungUntukTarget($paket->value, $paket->maxSantri());

                return [
                    'nama' => $paket->label(),
                    'harga' => $hitung['formatted'],
                    'kuota' => $hitung['kuota_maksimal'],
                    'populer' => $paket === PaketLangganan::Tumbuh,
                    'deskripsi' => $this->deskripsi($paket),
                    // Kuota paket Maju bisa dinaikkan lewat add-on, jadi CTA-nya
                    // mengarah ke form demo — angkanya perlu dibicarakan dulu.
                    'hubungiKami' => $paket === PaketLangganan::Maju,
                ];
            })
            ->all();
    }

    private function deskripsi(PaketLangganan $paket): string
    {
        return match ($paket) {
            PaketLangganan::Rintisan => 'Untuk pesantren yang baru merapikan pencatatan.',
            PaketLangganan::Tumbuh => 'Kapasitas yang pas untuk mayoritas pesantren.',
            PaketLangganan::Berkembang => 'Untuk pesantren menengah dengan banyak kelas.',
            PaketLangganan::Maju => 'Untuk pesantren besar, kuota bisa ditambah.',
        };
    }
}
