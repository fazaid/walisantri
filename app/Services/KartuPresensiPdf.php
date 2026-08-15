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
            $this->kartuUntukKelas($kelas),
            'kartu-presensi-'.str($kelas->nama_kelas)->slug().'.pdf',
            $kelas->nama_kelas,
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
            'qr' => $santri->kode_presensi
                ? $this->gambarQr(KodePresensi::payload($santri->kode_presensi))
                : null,
        ]);
    }

    /**
     * Satu gambar QR untuk satu payload.
     *
     * ⚠️ Instance QRCode dibuat BARU tiap panggilan, dan itu wajib, bukan gaya.
     * `QRCode::render()` di chillerlan/php-qrcode MENAMBAHKAN segmen data ke
     * instance-nya, bukan menggantikan. Versi pertama memakai satu instance untuk
     * seluruh kelas, sehingga kartu kedua memuat kode santri pertama DAN kedua,
     * kartu ketiga memuat ketiganya, dan seterusnya — matriksnya membesar tiap
     * kartu (28 → 32 → 36 baris) tanpa satu pun error.
     *
     * Gejalanya di lapangan menipu: kartu pertama sekelas selalu berhasil
     * dipindai, kartu berikutnya ditolak karena berisi beberapa kode sekaligus.
     * Yang tampak seperti masalah pemindai ternyata cacat di percetakannya.
     */
    private function gambarQr(string $payload): string
    {
        // PNG base64, bukan SVG: dukungan SVG DomPDF terbatas dan gagalnya diam —
        // gambarnya sekadar tidak muncul, tanpa error apa pun.
        return (new QRCode(new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 6,
            'imageBase64' => true,
        ])))->render($payload);
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
