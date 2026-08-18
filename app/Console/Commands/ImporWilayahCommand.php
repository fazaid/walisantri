<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Muat referensi wilayah Kemendagri (§3.1) dari berkas ter-vendor ke tabel `wilayah`.
 *
 * Dipanggil deploy.yml tepat setelah `migrate --force` dan oleh `composer setup`.
 * Idempoten: pada deploy normal ia hanya membandingkan COUNT dengan jumlah baris yang
 * tercatat di metadata berkas lalu keluar (~50 ms) — jadi aman dijalankan setiap rilis.
 *
 * Berkasnya sendiri dihasilkan `php artisan wilayah:regenerasi` dan di-commit.
 */
class ImporWilayahCommand extends Command
{
    protected $signature = 'wilayah:impor
        {--berkas= : Path berkas CSV/CSV.GZ (default database/data/wilayah.csv.gz)}
        {--paksa : Muat ulang walau jumlah baris sudah cocok}';

    protected $description = 'Muat data wilayah Kemendagri (provinsi s.d. desa/kelurahan) dari berkas ter-vendor ke tabel wilayah';

    /**
     * 500 baris × 4 kolom = 2.000 placeholder. Aman di build SQLite lawas (batas
     * variabel 999 per statement pada sebagian build, 32.766 pada yang baru) maupun
     * batas 65.535 PostgreSQL.
     */
    private const UKURAN_CHUNK = 500;

    public function handle(): int
    {
        $path = $this->option('berkas')
            ? base_path((string) $this->option('berkas'))
            : base_path('database/data/wilayah.csv.gz');

        if (! is_file($path)) {
            $this->error("Berkas wilayah tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $harapan = $this->bacaJumlahBarisMetadata($path);

        if (! $this->option('paksa') && $harapan !== null && DB::table('wilayah')->count() === $harapan) {
            $this->info('Data wilayah sudah sinkron ('.number_format($harapan, 0, ',', '.').' baris) — dilewati.');

            return self::SUCCESS;
        }

        $jumlah = $this->muat($path);

        // Daftar provinsi di-cache 1 hari (Wilayah::provinsi) — tanpa ini, dataset baru
        // baru terlihat di halaman register sehari kemudian.
        Cache::forget('wilayah:provinsi');

        $this->newLine();
        $this->info('Selesai: '.number_format($jumlah, 0, ',', '.').' baris dimuat.');

        return self::SUCCESS;
    }

    /**
     * Baris pertama berkas hasil `wilayah:regenerasi`:
     * `# sumber=…; diambil=2026-08-18; baris=91599`
     */
    private function bacaJumlahBarisMetadata(string $path): ?int
    {
        $handle = $this->buka($path);
        $baris = fgets($handle) ?: '';
        fclose($handle);

        return preg_match('/baris=(\d+)/', $baris, $cocok) ? (int) $cocok[1] : null;
    }

    private function muat(string $path): int
    {
        $handle = $this->buka($path);

        // Metadata + header kolom.
        fgets($handle);
        fgetcsv($handle, escape: '');

        $jumlah = 0;

        // Transaksional supaya deploy yang gagal di tengah tidak pernah meninggalkan
        // tabel separuh terisi — kondisi yang membuat /register menolak wilayah yang sah.
        DB::transaction(function () use ($handle, &$jumlah) {
            // delete(), bukan truncate(): TRUNCATE tidak ada di SQLite, dan di PostgreSQL
            // ia mengambil ACCESS EXCLUSIVE lock — tidak diinginkan saat deploy.
            DB::table('wilayah')->delete();

            $buffer = [];

            while (($baris = fgetcsv($handle, escape: '')) !== false) {
                if ($baris === [null] || ! isset($baris[0], $baris[1])) {
                    continue;
                }

                [$kode, $nama] = $baris;

                $buffer[] = [
                    'kode' => $kode,
                    'nama' => $nama,
                    // Diturunkan di sini, bukan disimpan di berkas: kodenya sudah memuat
                    // seluruh leluhurnya, jadi menyimpannya lagi hanya membesarkan berkas
                    // dan membuka peluang keduanya tidak sinkron.
                    'parent_kode' => str_contains($kode, '.') ? Str::beforeLast($kode, '.') : null,
                    'level' => substr_count($kode, '.') + 1,
                ];

                if (count($buffer) >= self::UKURAN_CHUNK) {
                    DB::table('wilayah')->insert($buffer);
                    $jumlah += count($buffer);
                    $buffer = [];
                }
            }

            if ($buffer !== []) {
                DB::table('wilayah')->insert($buffer);
                $jumlah += count($buffer);
            }
        });

        fclose($handle);

        return $jumlah;
    }

    /**
     * Dibaca streaming. JANGAN gzdecode(file_get_contents()) + explode: itu ~50 MB peak
     * dan bisa menabrak memory_limit PHP-CLI yang ketat di VPS.
     *
     * @return resource
     */
    private function buka(string $path)
    {
        return str_ends_with($path, '.gz')
            ? fopen('compress.zlib://'.$path, 'r')
            : fopen($path, 'r');
    }
}
