<?php

namespace App\Services;

use App\Models\BillingSetting;
use App\Models\Pesantren;

class BillingCalculatorService
{
    private function cfg(string $key, int $default): int
    {
        return BillingSetting::get($key, $default);
    }

    public function hitung(Pesantren $pesantren): array
    {
        return match ($pesantren->paket_langganan) {
            'rintisan'   => $this->paketTetap('Rintisan', $this->cfg('harga_rintisan', 150_000), $this->cfg('kuota_rintisan', 100)),
            'tumbuh'     => $this->paketTetap('Tumbuh', $this->cfg('harga_tumbuh', 299_000), $this->cfg('kuota_tumbuh', 250)),
            'berkembang' => $this->paketTetap('Berkembang', $this->cfg('harga_berkembang', 350_000), $this->cfg('kuota_berkembang', 500)),
            'maju'       => $this->paketMaju($pesantren->max_santri_kuota),
            default      => $this->paketTetap('Unknown', 0, 0),
        };
    }

    public function hitungUntukTarget(string $paket, int $maxSantri): array
    {
        return match ($paket) {
            'rintisan'   => $this->paketTetap('Rintisan', $this->cfg('harga_rintisan', 150_000), $this->cfg('kuota_rintisan', 100)),
            'tumbuh'     => $this->paketTetap('Tumbuh', $this->cfg('harga_tumbuh', 299_000), $this->cfg('kuota_tumbuh', 250)),
            'berkembang' => $this->paketTetap('Berkembang', $this->cfg('harga_berkembang', 350_000), $this->cfg('kuota_berkembang', 500)),
            'maju'       => $this->paketMaju($maxSantri),
            default      => $this->paketTetap('Unknown', 0, 0),
        };
    }

    // Formula PRD §5.3:
    // X = CEIL((N - kuota_maju_base) / 100)
    // Total = harga_maju_base + (X × harga_maju_per_100_santri)
    // Kuota = kuota_maju_base + (X × 100)
    public function paketMaju(int $quotaSantri): array
    {
        $base    = $this->cfg('kuota_maju_base', 1000);
        $hargaB  = $this->cfg('harga_maju_base', 750_000);
        $hargaX  = $this->cfg('harga_maju_per_100_santri', 100_000);

        $n  = max($quotaSantri, $base);
        $x  = max(0, (int) ceil(($n - $base) / 100));

        $totalBiaya    = $hargaB + ($x * $hargaX);
        $kuotaMaksimal = $base + ($x * 100);

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
