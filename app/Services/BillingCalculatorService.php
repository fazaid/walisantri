<?php

// File: app/Services/BillingCalculatorService.php
// Buat folder app/Services/ jika belum ada.

namespace App\Services;

use App\Models\Pesantren;

class BillingCalculatorService
{
    // Harga paket tetap (Rupiah) — PRD §5.1
    private const HARGA_GRATIS     = 0;
    private const HARGA_RINTISAN   = 150_000;
    private const HARGA_BERKEMBANG = 450_000;

    // Formula Paket Maju: Rp750.000 + (X × Rp100.000) — PRD §5.2
    private const HARGA_MAJU_BASE    = 750_000;
    private const HARGA_MAJU_PER_100 = 100_000;
    private const MAJU_KUOTA_BASE    = 1000;
    private const MAJU_KUOTA_STEP    = 100;

    public function hitung(Pesantren $pesantren): array
    {
        return match ($pesantren->paket_langganan) {
            'gratis'     => $this->paketTetap('Gratis', self::HARGA_GRATIS, 10),
            'rintisan'   => $this->paketTetap('Rintisan', self::HARGA_RINTISAN, 100),
            'berkembang' => $this->paketTetap('Berkembang', self::HARGA_BERKEMBANG, 500),
            'maju'       => $this->paketMaju($pesantren->max_santri_kuota),
            default      => $this->paketTetap('Unknown', 0, 0),
        };
    }

    // Formula PRD §5.2:
    // X = CEIL((N - 1000) / 100)
    // Total = Rp750.000 + (X × Rp100.000)
    // Kuota = 1000 + (X × 100)
    // Contoh: 1.200 santri → X=2 → kuota 1.200 → Rp 950.000/bulan
    public function paketMaju(int $quotaSantri): array
    {
        $n = max($quotaSantri, self::MAJU_KUOTA_BASE + 1);
        $x = (int) ceil(($n - self::MAJU_KUOTA_BASE) / self::MAJU_KUOTA_STEP);

        $totalBiaya    = self::HARGA_MAJU_BASE + ($x * self::HARGA_MAJU_PER_100);
        $kuotaMaksimal = self::MAJU_KUOTA_BASE + ($x * self::MAJU_KUOTA_STEP);

        return [
            'paket'          => 'Maju',
            'faktor_x'       => $x,
            'total_biaya'    => $totalBiaya,
            'kuota_maksimal' => $kuotaMaksimal,
            'formatted'      => 'Rp ' . number_format($totalBiaya, 0, ',', '.'),
        ];
    }

    private function paketTetap(string $nama, int $harga, int $kuota): array
    {
        return [
            'paket'          => $nama,
            'faktor_x'       => null,
            'total_biaya'    => $harga,
            'kuota_maksimal' => $kuota,
            'formatted'      => 'Rp ' . number_format($harga, 0, ',', '.'),
        ];
    }
}