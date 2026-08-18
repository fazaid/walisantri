<?php

namespace App\Services;

use App\Enums\DurasiLangganan;
use App\Enums\PaketLangganan;
use App\Models\BillingSetting;

/**
 * Sumber tunggal angka harga untuk halaman publik (/harga dan ringkasan di landing).
 *
 * Angkanya WAJIB lewat BillingCalculatorService + BillingSetting, bukan ditulis
 * langsung di Blade — harga hardcode akan diam-diam menyimpang begitu super admin
 * mengubahnya di BillingSettingsPage. Dua halaman kini memajang angka yang sama,
 * jadi perhitungannya tinggal di sini, bukan di salah satu controller.
 */
class PaketHargaService
{
    public function __construct(private BillingCalculatorService $kalkulator) {}

    /**
     * Kartu harga: satu entri per paket, lengkap dengan pasangan bulanan/tahunan.
     */
    public function kartu(): array
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

    /**
     * Angka yang dipakai salinan seputar siklus (label tab, catatan kaki, FAQ).
     * Di-spread ke data view supaya kedua halaman memakai kunci yang sama.
     */
    public function konteksSiklus(): array
    {
        $tahunan = DurasiLangganan::DuabelasBulan;

        return [
            'bonusEnam' => DurasiLangganan::EnamBulan->bonusBulan(),
            'bonusTahunan' => $tahunan->bonusBulan(),
            'bulanBayarTahunan' => $tahunan->bulanBayar(),
            'totalBulanTahunan' => $tahunan->totalBulan(),
        ];
    }

    /**
     * Harga paket termurah — dipakai kalimat "mulai Rp ..." di ringkasan landing
     * dan di FAQ-nya. Dihitung dari kartu, bukan diasumsikan Rintisan, supaya
     * urutan harga yang tidak lazim di BillingSetting tidak membuatnya berbohong.
     */
    public function hargaTerendah(): string
    {
        $terendah = collect(PaketLangganan::cases())
            ->map(fn (PaketLangganan $paket) => $this->kalkulator
                ->hitungUntukTarget($paket->value, $paket->maxSantri())['total_biaya'])
            ->min();

        return $this->rupiah((int) $terendah);
    }

    /**
     * Add-on kuota paket Maju (§5.3). Contohnya ikut dihitung lewat kalkulator,
     * bukan ditulis tangan, jadi ia tetap benar saat setelannya digeser.
     */
    public function addOnMaju(): array
    {
        $base = BillingSetting::get('kuota_maju_base', 1000);
        $per100 = BillingSetting::get('harga_maju_per_100_santri', 100_000);
        $contohSantri = $base + 200;
        $contoh = $this->kalkulator->paketMaju($contohSantri);

        return [
            'kuotaBase' => $base,
            'hargaPer100' => $this->rupiah($per100),
            'contohSantri' => $contohSantri,
            'contohKuota' => $contoh['kuota_maksimal'],
            'contohHarga' => $contoh['formatted'],
        ];
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
