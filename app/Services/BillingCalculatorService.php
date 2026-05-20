<?php

// File: app/Services/BillingCalculatorService.php
// Buat folder app/Services/ jika belum ada.

namespace App\Services;

use App\Models\Pesantren;

class BillingCalculatorService
{
    // Harga dasar paket tetap (Rupiah)
    private const HARGA_RINTISAN   = 150_000;
    private const HARGA_BERKEMBANG = 300_000;
    private const HARGA_AKSELERASI = 600_000;

    // Formula Paket Besar: Rp600.000 + (X × Rp100.000)
    private const HARGA_BESAR_BASE    = 600_000;
    private const HARGA_BESAR_PER_100 = 100_000;
    private const BESAR_KUOTA_BASE    = 500;
    private const BESAR_KUOTA_STEP    = 100;

    public function hitung(Pesantren $pesantren): array
    {
        return match ($pesantren->paket_langganan) {
            'rintisan'   => $this->paketTetap(
                'Rintisan', self::HARGA_RINTISAN, 100
            ),
            'berkembang' => $this->paketTetap(
                'Berkembang', self::HARGA_BERKEMBANG, 250
            ),
            'akselerasi' => $this->paketTetap(
                'Akselerasi', self::HARGA_AKSELERASI, 500
            ),
            'besar'      => $this->paketBesar($pesantren->max_santri_kuota),
            default      => $this->paketTetap('Unknown', 0, 0),
        };
    }

    // Formula PRD §5.2:
    // X = ceil((N - 500) / 100)
    // Total = Rp600.000 + (X × Rp100.000)
    // Kuota = 500 + (X × 100)
    public function paketBesar(int $quotaSantri): array
    {
        $n = max($quotaSantri, self::BESAR_KUOTA_BASE + 1);
        $x = (int) ceil(($n - self::BESAR_KUOTA_BASE) / self::BESAR_KUOTA_STEP);

        $totalBiaya      = self::HARGA_BESAR_BASE + ($x * self::HARGA_BESAR_PER_100);
        $kuotaMaksimal   = self::BESAR_KUOTA_BASE + ($x * self::BESAR_KUOTA_STEP);

        return [
            'paket'          => 'Besar',
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