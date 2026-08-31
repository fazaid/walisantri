<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Support\GambarQr;
use App\Support\KodePresensi;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kartu santri: tanda pengenal berfoto, satu kartu satu halaman.
 *
 * Berbeda dari KartuPresensiPdf yang lembar A4 berisi banyak QR untuk dipindai
 * mesin, yang ini benda yang dipegang santri. Karena itu kertasnya diset seukuran
 * kartunya sendiri (CR80, 85,6 × 54 mm) alih-alih A4 bergaris potong: hasil potong
 * tangan dari lembar A4 tidak pernah cukup lurus untuk dilaminating, dan pesantren
 * yang punya printer kartu PVC bisa mencetak langsung tanpa mengatur apa pun.
 *
 * QR-nya sama persis dengan kartu presensi — payload `WSP1.{kode_presensi}` yang
 * sama, dibuat generator yang sama. Jadi satu santri yang memegang kartu identitas
 * ini tidak perlu juga membawa kartu QR: keduanya terbaca pemindai yang sama.
 *
 * ⚠️ `santri.uuid` TIDAK BOLEH muncul di kartu ini, sama seperti di kartu presensi
 * (§13.2). Ia token bearer Magic Link portal wali; kartu identitas berpindah tangan
 * dan dipotret — bahkan lebih sering daripada kartu presensi, karena ia dipegang
 * setiap hari. Identifier yang boleh dicetak hanya `nis` dan `kode_presensi`.
 */
class KartuSantriPdf
{
    /**
     * Batas panjang teks yang muat di kartu 85,6 × 54 mm.
     *
     * ⚠️ Ini bukan kosmetik. Kartunya setinggi persis satu halaman PDF, jadi teks
     * yang membungkus satu baris lebih panjang dari perkiraan mendorong kartu ke
     * halaman kedua — dan DomPDF melakukannya TANPA error apa pun, jadi gejalanya
     * cuma "PDF-nya jadi dua kali lebih banyak halaman". Angkanya diukur, bukan
     * ditebak: dengan nilai ini, kartu terburuk (semua kolom terisi, nama panjang,
     * foto ada) butuh 48 mm dari 53 mm yang tersedia.
     *
     * Konstanta supaya tes ikut memakainya — kalau layoutnya berubah, satu tempat
     * yang disesuaikan, dan tes tinggi halaman yang menjaganya.
     */
    public const PANJANG_ALAMAT = 45;

    /** Alamat pesantren di kop; lebih dari ini membungkus jadi baris ketiga. */
    public const PANJANG_ALAMAT_PESANTREN = 55;

    public function untukKelas(Kelas $kelas, CarbonInterface $masaBerlaku): Response
    {
        return $this->render(
            $this->kartuUntukKelas($kelas),
            $masaBerlaku,
            'kartu-santri-'.str($kelas->nama_kelas)->slug().'.pdf',
        );
    }

    public function untukSantri(Santri $santri, CarbonInterface $masaBerlaku): Response
    {
        return $this->render(
            $this->petakan(collect([$santri])),
            $masaBerlaku,
            'kartu-santri-'.str($santri->nama_lengkap)->slug().'.pdf',
        );
    }

    /** Publik supaya isi tiap kartu bisa diperiksa tes tanpa membedah PDF. */
    public function kartuUntukKelas(Kelas $kelas): Collection
    {
        return $this->petakan(
            Santri::with(['kelas', 'kamar'])
                ->where('kelas_id', $kelas->id)
                ->where('status_aktif', true)
                ->orderBy('nama_lengkap')
                ->get()
        );
    }

    /** @param Collection<int, Santri> $santriList */
    private function petakan(Collection $santriList): Collection
    {
        return $santriList->map(fn (Santri $santri) => (object) [
            'nama' => $santri->nama_lengkap,
            'nis' => $santri->nis,
            'kelas' => $santri->kelas?->nama_kelas,
            'kamar' => $santri->kamar?->nama_kamar,
            'jenis_kelamin' => $santri->jenis_kelamin?->label(),
            'tanggal_lahir' => $santri->tanggal_lahir?->translatedFormat('d F Y'),
            // Dipotong di sini, bukan di blade: alamat lengkap sering satu paragraf
            // dan kartunya cuma 54 mm — memotongnya lewat CSS overflow membuat DomPDF
            // menumpuk teks ke luar bingkai alih-alih menyembunyikannya.
            'alamat' => str($santri->alamat_lengkap ?? '')->limit(self::PANJANG_ALAMAT)->value() ?: null,
            'foto' => $santri->foto_profil_path,
            // Kode dicetak sebagai teks juga, sama seperti di kartu presensi:
            // QR di kartu yang dipakai harian akan lecek lebih cepat, dan petugas
            // harus punya cara mencatat kehadiran tanpa menyuruh anaknya pulang.
            'kode' => $santri->kode_presensi,
            // Skala 4, bukan 6 seperti kartu presensi: di sini QR-nya cuma 14 mm,
            // dan PNG yang jauh lebih besar dari ruang cetaknya hanya membengkakkan
            // berkas — satu kelas berisi puluhan kartu.
            'qr' => $santri->kode_presensi
                ? GambarQr::dataUri(KodePresensi::payload($santri->kode_presensi), 4)
                : null,
        ]);
    }

    /**
     * Identitas pesantren dibaca sekali di luar loop kartu.
     *
     * Lewat sesi, bukan lewat `$santri->pesantren`: kartu selalu dicetak dari panel
     * admin yang sudah ter-scope tenant, dan membacanya dari relasi berarti satu
     * query per kartu untuk data yang sama persis di seluruh kelas.
     */
    private function pesantren(): ?Pesantren
    {
        return Auth::user()?->pesantren;
    }

    /**
     * Seluruh isi yang dilihat template.
     *
     * Publik dengan alasan yang sama seperti `kartuUntukKelas()`: isi PDF tidak bisa
     * diperiksa harfiah dari luar — DomPDF mengompresi aliran teksnya, jadi
     * `assertStringContainsString('Kepala Pesantren', $pdf)` akan lulus-palsu atau
     * gagal-palsu tergantung font. Tes merender blade-nya sendiri dengan array ini.
     *
     * @return array<string, mixed>
     */
    public function dataView(Collection $kartu, CarbonInterface $masaBerlaku): array
    {
        $pesantren = $this->pesantren();

        return [
            'kartu' => $kartu,
            // ⚠️ Locale dipatok, bukan mengikuti config. Seluruh label di kartu ini
            // ditulis tetap dalam bahasa Indonesia ("Berlaku sampai", "Kepala
            // Pesantren"), jadi bulan yang mengikuti `app.locale` menghasilkan kartu
            // setengah Inggris — "Berlaku sampai 30 June 2027" — di lingkungan mana
            // pun yang APP_LOCALE-nya belum diset, tanpa error apa pun. Itu bukan
            // hipotesis: tes berjalan tanpa APP_LOCALE dan memang mencetak "June".
            // ->copy() supaya instance milik pemanggil tidak ikut berubah locale-nya.
            'masaBerlaku' => $masaBerlaku->copy()->locale('id')->translatedFormat('d F Y'),
            'namaPesantren' => $pesantren?->nama_pesantren,
            'alamatPesantren' => str($pesantren?->alamatLengkap() ?? '')
                ->limit(self::PANJANG_ALAMAT_PESANTREN)->value() ?: null,
            'logoPesantren' => $pesantren?->logo_path,
            'kepalaPesantren' => $pesantren?->kepala_pesantren,
        ];
    }

    private function render(Collection $kartu, CarbonInterface $masaBerlaku, string $namaBerkas): Response
    {
        $pdf = Pdf::loadView('filament.pdf.kartu-santri', $this->dataView($kartu, $masaBerlaku))
            // CR80 85,6 × 54 mm dalam poin (1 mm = 72/25,4 pt). Halaman seukuran
            // kartunya sendiri; blade tinggal mengisi penuh tanpa garis potong.
            ->setPaper([0, 0, 242.65, 153.07]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $namaBerkas,
        );
    }
}
