<?php

namespace App\Support;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Satu-satunya tempat gambar QR dibuat di aplikasi ini.
 *
 * Dipusatkan karena QR yang sama dipakai di tiga permukaan — PDF kartu presensi,
 * pratinjau di halaman detail santri, dan kartu apa pun yang menyusul. Selama
 * pembuatannya tersebar, peringatan di bawah harus disalin ke tiap salinan, dan
 * salinan yang terlewat gagal tanpa suara.
 */
class GambarQr
{
    /**
     * Satu gambar QR untuk satu payload, sebagai data URI PNG.
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
     *
     * PNG base64, bukan SVG: dukungan SVG DomPDF terbatas dan gagalnya diam —
     * gambarnya sekadar tidak muncul, tanpa error apa pun.
     */
    public static function dataUri(string $payload, int $skala = 6): string
    {
        return (new QRCode(new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => $skala,
            'imageBase64' => true,
        ])))->render($payload);
    }
}
