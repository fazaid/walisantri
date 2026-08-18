<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Dev-only: ubah dump SQL Kemendagri dari cahyadsn/wilayah menjadi berkas
 * ter-vendor `database/data/wilayah.csv.gz` yang dibaca `wilayah:impor`.
 *
 * TIDAK PERNAH dipanggil CI maupun deploy. Dijalankan manual oleh developer saat
 * Kemendagri merilis dataset baru, lalu berkas hasilnya di-commit — VPS dan CI
 * tidak boleh bergantung pada GitHub raw saat deploy.
 *
 * Kenapa command PHP dan bukan one-liner grep/sed: 437 nama desa memuat apostrof
 * ter-escape gaya MySQL (`'Pasi Kuala Ba''u'`). Parser naif memotong baris itu di
 * tengah dan menghasilkan data rusak TANPA galat apa pun — kelas kegagalan paling
 * mungkin di seluruh jalur ini.
 */
class RegenerasiBerkasWilayahCommand extends Command
{
    protected $signature = 'wilayah:regenerasi
        {--sumber=https://raw.githubusercontent.com/cahyadsn/wilayah/master/db/wilayah.sql : URL atau path lokal dump SQL upstream}
        {--keluaran=database/data/wilayah.csv.gz : Tujuan berkas CSV terkompresi}';

    protected $description = '[dev] Regenerasi database/data/wilayah.csv.gz dari dump SQL Kemendagri (cahyadsn/wilayah)';

    /** Ambang kewarasan: dataset resmi ±91.600 baris. Di bawah ini berarti parser/upstream berubah. */
    private const MINIMAL_BARIS = 90000;

    public function handle(): int
    {
        $sumber = (string) $this->option('sumber');

        $this->info("Mengambil {$sumber} …");

        $sql = @file_get_contents($sumber);

        if ($sql === false) {
            $this->error("Gagal membaca sumber: {$sumber}");

            return self::FAILURE;
        }

        // Pola sadar-escape: isi nama boleh apa pun kecuali kutip tunggal tunggal —
        // pasangan '' (escape MySQL) dan \' keduanya ikut tertangkap oleh alternasi.
        preg_match_all(
            "/\\('(\\d{2}(?:\\.\\d{2}){0,2}(?:\\.\\d{4})?)',\\s*'((?:[^']|''|\\\\')*)'\\)/",
            $sql,
            $cocok,
            PREG_SET_ORDER
        );

        $jumlah = count($cocok);

        if ($jumlah < self::MINIMAL_BARIS) {
            $this->error("Hanya {$jumlah} baris terbaca (minimal ".self::MINIMAL_BARIS.'). Format upstream kemungkinan berubah — berkas TIDAK ditulis.');

            return self::FAILURE;
        }

        $keluaran = base_path((string) $this->option('keluaran'));

        if (! is_dir(dirname($keluaran))) {
            mkdir(dirname($keluaran), 0755, true);
        }

        $handle = fopen('compress.zlib://'.$keluaran, 'w');

        // Baris metadata dibaca kembali oleh wilayah:impor sebagai penentu idempotensi
        // (jumlah baris harapan), sekaligus jejak asal-usul data di dalam berkas biner.
        fwrite($handle, '# sumber='.$sumber.'; diambil='.now()->toDateString().'; baris='.$jumlah."\n");
        fputcsv($handle, ['kode', 'nama'], escape: '');

        $perLevel = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

        foreach ($cocok as [, $kode, $nama]) {
            // Casing disimpan APA ADANYA. Str::title() merusak 'DKI JAKARTA' → 'Dki Jakarta'
            // dan 'DI YOGYAKARTA' → 'Di Yogyakarta'.
            $nama = str_replace(["''", "\\'"], "'", $nama);

            fputcsv($handle, [$kode, $nama], escape: '');
            $perLevel[substr_count($kode, '.') + 1]++;
        }

        fclose($handle);

        $this->info(sprintf(
            'Ditulis %s — %s baris (%s provinsi, %s kab/kota, %s kecamatan, %s desa), %s KB.',
            $this->option('keluaran'),
            number_format($jumlah, 0, ',', '.'),
            $perLevel[1], $perLevel[2], $perLevel[3], $perLevel[4],
            number_format(filesize($keluaran) / 1024, 0, ',', '.')
        ));

        return self::SUCCESS;
    }
}
