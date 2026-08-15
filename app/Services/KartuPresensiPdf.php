<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Santri;
use App\Support\KodePresensi;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kartu presensi ber-QR, dicetak per kelas.
 *
 * Isi QR adalah string opaque `WSP1.{kode}`, BUKAN URL. Konsekuensinya disengaja:
 * kamera bawaan ponsel yang memindai kartu ini tidak menawarkan "buka tautan" —
 * hasilnya teks tak bermakna, sehingga kartu tidak mengundang eksperimen. Dan yang
 * paling penting, kode itu bukan `santri.uuid`: uuid adalah token bearer Magic
 * Link, dan mencetaknya di kartu sama dengan membagikan sesi portal wali (§13.2).
 */
class KartuPresensiPdf
{
    public function untukKelas(Kelas $kelas): Response
    {
        return $this->render(
            $this->santriDenganQr(
                Santri::where('kelas_id', $kelas->id)
                    ->where('status_aktif', true)
                    ->orderBy('nama_lengkap')
                    ->get()
            ),
            'kartu-presensi-'.str($kelas->nama_kelas)->slug().'.pdf',
            $kelas->nama_kelas,
        );
    }

    /** @param Collection<int, Santri> $santriList */
    private function santriDenganQr(Collection $santriList): Collection
    {
        // PNG base64, bukan SVG: dukungan SVG DomPDF terbatas dan gagalnya diam —
        // gambarnya sekadar tidak muncul, tanpa error apa pun.
        $qr = new QRCode(new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 6,
            'imageBase64' => true,
        ]));

        return $santriList->map(fn (Santri $santri) => (object) [
            'nama' => $santri->nama_lengkap,
            'nis' => $santri->nis,
            'kelas' => $santri->kelas?->nama_kelas,
            // Kode ditampilkan juga sebagai teks — kalau QR-nya lecek atau kamera
            // gagal, petugas masih bisa mengetiknya. Alfabet Crockford memang
            // dipilih supaya terbaca manusia (tanpa I/L/O/U).
            'kode' => $santri->kode_presensi,
            'qr' => $santri->kode_presensi
                ? $qr->render(KodePresensi::payload($santri->kode_presensi))
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
