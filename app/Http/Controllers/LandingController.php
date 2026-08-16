<?php

namespace App\Http\Controllers;

use App\Enums\DurasiLangganan;
use App\Enums\PaketLangganan;
use App\Models\PlatformSetting;
use App\Services\BillingCalculatorService;
use App\Support\SandboxDemo;

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
                // Lihat catatan di WaliLoginController::redirectAfterLogin().
                'wali_santri' => redirect()->away(auth()->user()->urlPortalWali()),
                default => redirect('/admin'),
            };
        }

        $tahunan = DurasiLangganan::DuabelasBulan;

        return view('landing', [
            'registrationOpen' => PlatformSetting::registrationOpen(),
            'demoOpen' => PlatformSetting::demoOpen(),
            'demoWaliUrl' => SandboxDemo::waliUrl(),
            'paketList' => $this->paketList(),
            'bonusEnam' => DurasiLangganan::EnamBulan->bonusBulan(),
            'bonusTahunan' => $tahunan->bonusBulan(),
            'bulanBayarTahunan' => $tahunan->bulanBayar(),
            'totalBulanTahunan' => $tahunan->totalBulan(),
        ]);
    }

    /**
     * Kartu harga landing. Angkanya WAJIB lewat BillingCalculatorService supaya
     * mengikuti BillingSetting — harga yang ditulis langsung di Blade akan diam-diam
     * menyimpang begitu super admin mengubahnya di BillingSettingsPage.
     */
    private function paketList(): array
    {
        $tahunan = DurasiLangganan::DuabelasBulan;

        return collect(PaketLangganan::cases())
            ->map(function (PaketLangganan $paket) use ($tahunan) {
                $hitung = $this->kalkulator->hitungUntukTarget($paket->value, $paket->maxSantri());
                $perBulan = $hitung['total_biaya'];
                $kuota = $hitung['kuota_maksimal'];
                $totalTahunan = $perBulan * $tahunan->bulanBayar();

                // Bonus tahunan dibayarkan sebagai bulan gratis, bukan potongan harga:
                // wali membayar bulanBayar() bulan dan langganan aktif totalBulan().
                $hemat = $perBulan * $tahunan->bonusBulan();

                return [
                    'nama' => $paket->label(),
                    'harga' => $hitung['formatted'],
                    'hargaTahunan' => $this->rupiah($totalTahunan),
                    'hargaTahunanNormal' => $this->rupiah($perBulan * $tahunan->totalBulan()),
                    'hematTahunan' => $this->rupiah($hemat),
                    'adaHematTahunan' => $hemat > 0,
                    // Angka yang ditonjolkan di kartu. Ini tarif setara pada kuota
                    // penuh, bukan cara penagihan: tagihannya tetap per paket, jadi
                    // salinan di Blade wajib menyebut kuota yang dipakai membaginya.
                    'perSantriBulanan' => $this->perSantri($perBulan, $kuota),
                    'perSantriTahunan' => $this->perSantri($totalTahunan, $kuota),
                    'kuota' => $kuota,
                    'populer' => $paket === PaketLangganan::Tumbuh,
                    'deskripsi' => $this->deskripsi($paket),
                    // Kuota paket Maju bisa dinaikkan lewat add-on, jadi CTA-nya
                    // mengarah ke form demo — angkanya perlu dibicarakan dulu.
                    'hubungiKami' => $paket === PaketLangganan::Maju,
                ];
            })
            ->all();
    }

    private function rupiah(int $nominal): string
    {
        return 'Rp '.number_format($nominal, 0, ',', '.');
    }

    /**
     * Kuota dibaca dari BillingSetting lewat BillingCalculatorService, jadi nilainya
     * bisa saja tidak habis membagi harga — dibulatkan, dan salinan di Blade menyebut
     * "setara" supaya angka ini tidak terbaca sebagai tarif yang benar-benar ditagih.
     */
    private function perSantri(int $nominal, int $kuota): string
    {
        return $kuota > 0 ? $this->rupiah((int) round($nominal / $kuota)) : '-';
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
