<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Santri;
use App\Support\GambarQr;
use App\Support\KodePresensi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kartu presensi ber-QR, dicetak per kelas atau per santri.
 *
 * Isi QR adalah string opaque `WSP1.{kode}`, BUKAN URL. Konsekuensinya disengaja:
 * kamera bawaan ponsel yang memindai kartu ini tidak menawarkan "buka tautan" —
 * hasilnya teks tak bermakna, sehingga kartu tidak mengundang eksperimen. Dan yang
 * paling penting, kode itu bukan `santri.uuid`: uuid adalah token bearer Magic
 * Link, dan mencetaknya di kartu sama dengan membagikan sesi portal wali (§13.2).
 *
 * Kembarannya adalah KartuSantriPdf — kartu identitas berfoto. Keduanya sengaja
 * jadi dua benda terpisah: yang ini dipindai mesin dan boleh difotokopi murah,
 * yang satunya dipegang santri sebagai tanda pengenal.
 */
class KartuPresensiPdf
{
    public function untukKelas(Kelas $kelas): Response
    {
        return $this->render(
            $this->kartuUntukKelas($kelas),
            'kartu-presensi-'.str($kelas->nama_kelas)->slug().'.pdf',
            $kelas->nama_kelas,
        );
    }

    /**
     * Satu kartu untuk satu santri — jalan masuk dari halaman detail santri.
     *
     * Sengaja memakai template dan ukuran kertas yang sama dengan cetak sekelas,
     * bukan lembar khusus: kartu pengganti yang dicetak sendirian harus keluar
     * sama persis dengan kartu yang sedang dipegang teman sekelasnya, kalau tidak
     * petugas presensi melihat dua benda yang berbeda untuk fungsi yang sama.
     */
    public function untukSantri(Santri $santri): Response
    {
        return $this->render(
            $this->santriDenganQr(collect([$santri])),
            'kartu-presensi-'.str($santri->nama_lengkap)->slug().'.pdf',
            $santri->nama_lengkap,
        );
    }

    /** Publik supaya isi tiap kartu — termasuk QR-nya — bisa diperiksa tes. */
    public function kartuUntukKelas(Kelas $kelas): Collection
    {
        return $this->santriDenganQr(
            Santri::where('kelas_id', $kelas->id)
                ->where('status_aktif', true)
                ->orderBy('nama_lengkap')
                ->get()
        );
    }

    /** @param Collection<int, Santri> $santriList */
    private function santriDenganQr(Collection $santriList): Collection
    {
        return $santriList->map(fn (Santri $santri) => (object) [
            'nama' => $santri->nama_lengkap,
            'nis' => $santri->nis,
            'kelas' => $santri->kelas?->nama_kelas,
            // Kode ditampilkan juga sebagai teks — kalau QR-nya lecek atau kamera
            // gagal, petugas masih bisa mengetiknya. Alfabet Crockford memang
            // dipilih supaya terbaca manusia (tanpa I/L/O/U).
            'kode' => $santri->kode_presensi,
            // Pembuatan gambarnya ada di GambarQr, lengkap dengan peringatan
            // instance-yang-tidak-boleh-dipakai-ulang. Jangan inline-kan kembali.
            'qr' => $santri->kode_presensi
                ? GambarQr::dataUri(KodePresensi::payload($santri->kode_presensi))
                : null,
        ]);
    }

    private function render(Collection $kartu, string $namaBerkas, string $judul): Response
    {
        $pdf = Pdf::loadView('filament.pdf.kartu-presensi', [
            'kartu' => $kartu,
            'judul' => $judul,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $namaBerkas,
        );
    }
}
