<?php

namespace App\Services\Rapor;

use App\Enums\StatusKehadiran;
use App\Models\Santri;
use App\Services\PresensiRekap;
use App\Services\TahunAjaranOptions;

/**
 * Data presensi satu santri untuk satu periode rapor.
 *
 * ⚠️ Berbeda dari tiga saudaranya di namespace ini, kelas ini TIDAK menghitung
 * sendiri. Agregasinya diserahkan penuh ke `App\Services\PresensiRekap` — sumber
 * yang sama dengan halaman Rekap admin, ekspor Excel, dan halaman presensi portal
 * wali. Alasannya pelajaran v4.19: halaman rapor dan PDF-nya dulu punya versi
 * query masing-masing, lalu menyimpang, dan menyimpangnya baru ketahuan setahun
 * kemudian. "Hari efektif" dan definisi "% kehadiran" terlalu mudah diselisihkan
 * untuk boleh ditulis dua kali.
 */
class RaporPresensiData
{
    /**
     * @return array{ada_data: bool, hari_efektif: int, hadir_efektif: int, persen_kehadiran: int, tanpa_keterangan: int, total_tercatat: int, status: list<array{label: string, jumlah: int}>, awal: string, akhir: string}
     */
    public static function untuk(int $santriId, string $tahunAjaran, string $periode, ?string $bulan = null): array
    {
        [$awal, $akhir] = TahunAjaranOptions::rentangTanggal($tahunAjaran, $periode, $bulan);

        $santri = Santri::find($santriId);

        if (! $santri) {
            return self::kosong($awal, $akhir);
        }

        $rekap = PresensiRekap::untuk($santri->pesantren_id, $awal, $akhir, santriId: $santri->id);
        $baris = $rekap->satuSantri();

        // Batas atas rentang dipotong ke hari ini di dalam PresensiRekap; yang
        // dicetak di rapor harus rentang yang BENAR-BENAR dihitung, bukan rentang
        // periode yang diminta — kalau tidak, rapor semester ganjil yang dicetak
        // pertengahan Oktober akan mengklaim mencakup sampai 31 Desember.
        [, $akhirEfektif] = $rekap->rentang();

        if (! $baris) {
            return self::kosong($awal, $akhirEfektif);
        }

        $tercatat = (int) $baris->total_tercatat;

        return [
            // Nol baris = modul ini tidak dicetak sama sekali. Mencetak "0%
            // kehadiran" untuk pesantren yang memang belum mulai mengabsen adalah
            // tuduhan, bukan laporan.
            'ada_data' => $tercatat > 0,
            'hari_efektif' => (int) $baris->hari_efektif,
            'hadir_efektif' => (int) $baris->hadir_efektif,
            'persen_kehadiran' => (int) $baris->persen_kehadiran,
            'tanpa_keterangan' => (int) $baris->tanpa_keterangan,
            'total_tercatat' => $tercatat,
            'status' => array_map(fn (StatusKehadiran $status): array => [
                'label' => $status->label(),
                'jumlah' => (int) $baris->{$rekap->kolomStatus($status)},
            ], StatusKehadiran::cases()),
            'awal' => $awal,
            'akhir' => $akhirEfektif,
        ];
    }

    /** @return array<string, mixed> */
    private static function kosong(string $awal, string $akhir): array
    {
        return [
            'ada_data' => false,
            'hari_efektif' => 0,
            'hadir_efektif' => 0,
            'persen_kehadiran' => 0,
            'tanpa_keterangan' => 0,
            'total_tercatat' => 0,
            'status' => [],
            'awal' => $awal,
            'akhir' => $akhir,
        ];
    }
}
