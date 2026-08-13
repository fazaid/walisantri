<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pemeriksaan sebelum `php artisan migrate --force` dijalankan saat deploy.
 *
 * Alasannya konkret: skrip deploy melakukan `git pull` + `composer install`
 * SEBELUM migrasi. Kalau migrasi gagal di tengah, `set -e` menghentikan skrip dan
 * `trap` mengembalikan situs online — jadi hasil akhirnya kode baru berjalan di
 * atas skema lama. Situs online tapi rusak lebih sulit ditangani daripada situs
 * yang jujur 503, maka lebih baik gagal SEBELUM deploy dimulai.
 *
 * Perintah ini HANYA MEMBACA. Satu-satunya jalur yang menulis adalah
 * --perbaiki-periode, yang harus diminta eksplisit dan masih menanyakan konfirmasi.
 */
class DeployPreflightCommand extends Command
{
    protected $signature = 'deploy:preflight
        {--perbaiki-periode : Konversi periode "Semester" lama jadi Semester_Ganjil/Genap (menulis ke DB)}';

    protected $description = 'Periksa apakah migrasi yang tertunda aman dijalankan di database ini';

    /** @var list<array{status: string, judul: string, pesan: string}> */
    private array $hasil = [];

    private const BLOCK = 'BLOCK';

    private const WARN = 'WARN';

    private const OK = 'OK';

    public function handle(Migrator $migrator): int
    {
        $this->info('Pre-flight check — '.config('app.env').' @ '.DB::connection()->getDatabaseName());
        $this->newLine();

        $tertunda = $this->migrasiTertunda($migrator);

        $this->tampilkanMigrasiTertunda($tertunda);

        if ($tertunda === []) {
            $this->info('Tidak ada migrasi tertunda — tidak ada yang perlu diperiksa.');

            return self::SUCCESS;
        }

        $this->periksaPeriodeKarakter($tertunda);
        $this->periksaUniqueInventaris($tertunda);
        $this->periksaAmalMaster($tertunda);

        return $this->laporkan();
    }

    /** @return list<string> nama migrasi yang belum tercatat di tabel migrations */
    private function migrasiTertunda(Migrator $migrator): array
    {
        if (! $migrator->repositoryExists()) {
            return [];
        }

        $sudah = $migrator->getRepository()->getRan();

        $paths = array_merge($migrator->paths(), [database_path('migrations')]);

        return collect($migrator->getMigrationFiles($paths))
            ->keys()
            ->reject(fn (string $nama) => in_array($nama, $sudah, true))
            ->values()
            ->all();
    }

    /** @param  list<string>  $tertunda */
    private function tampilkanMigrasiTertunda(array $tertunda): void
    {
        if ($tertunda === []) {
            return;
        }

        $this->line('<comment>'.count($tertunda).' migrasi akan dijalankan, urut:</comment>');
        foreach ($tertunda as $i => $nama) {
            $this->line(sprintf('  %2d. %s', $i + 1, $nama));
        }
        $this->newLine();
    }

    /**
     * PostgreSQL memvalidasi seluruh baris yang ada saat ADD CONSTRAINT CHECK.
     * Tabel ini dibuat dengan enum('Bulanan','Semester'), jadi rapor semester yang
     * diinput sebelum v4.9 bernilai 'Semester' — nilai yang dibuang CHECK baru.
     * Tidak ada migrasi yang mengonversi data lamanya, jadi harus dicek manual.
     *
     * @param  list<string>  $tertunda
     */
    private function periksaPeriodeKarakter(array $tertunda): void
    {
        $migrasi = '2026_07_25_000001_update_periode_check_on_kesantrian_karakter_rapor_table';

        if (! in_array($migrasi, $tertunda, true)) {
            return;
        }

        if (! Schema::hasTable('kesantrian_karakter_rapor')) {
            return;
        }

        $jumlah = DB::table('kesantrian_karakter_rapor')->where('periode', 'Semester')->count();

        if ($jumlah === 0) {
            $this->catat(self::OK, 'CHECK periode karakter', 'Tidak ada baris periode "Semester".');

            return;
        }

        if ($this->option('perbaiki-periode')) {
            $this->perbaikiPeriode($jumlah);

            return;
        }

        $this->catat(self::BLOCK, 'CHECK periode karakter', sprintf(
            "%d baris kesantrian_karakter_rapor masih berperiode 'Semester'.\n".
            "      CHECK baru hanya menerima Bulanan/Semester_Ganjil/Semester_Genap,\n".
            "      sehingga `migrate --force` akan gagal di migrasi ke-%d.\n".
            '      Perbaiki dengan: php artisan deploy:preflight --perbaiki-periode',
            $jumlah,
            array_search($migrasi, $tertunda, true) + 1,
        ));
    }

    private function perbaikiPeriode(int $jumlah): void
    {
        $this->warn("Akan mengubah {$jumlah} baris kesantrian_karakter_rapor.");
        $this->line('  Juli–Desember → Semester_Ganjil, Januari–Juni → Semester_Genap (dari tanggal_input).');

        if (! $this->confirm('Lanjutkan?', false)) {
            $this->catat(self::BLOCK, 'CHECK periode karakter', 'Dibatalkan pengguna — periode lama belum dikonversi.');

            return;
        }

        // Sengaja dihitung di PHP, bukan lewat EXTRACT/strftime: sintaks tanggal
        // beda antara Postgres dan SQLite, dan barisnya sedikit.
        $diubah = 0;

        DB::table('kesantrian_karakter_rapor')
            ->where('periode', 'Semester')
            ->select('id', 'tanggal_input')
            ->orderBy('id')
            ->chunkById(500, function ($baris) use (&$diubah) {
                foreach ($baris as $row) {
                    $bulan = (int) Carbon::parse($row->tanggal_input)->format('n');

                    DB::table('kesantrian_karakter_rapor')
                        ->where('id', $row->id)
                        ->update(['periode' => $bulan >= 7 ? 'Semester_Ganjil' : 'Semester_Genap']);

                    $diubah++;
                }
            });

        $this->catat(self::OK, 'CHECK periode karakter', "{$diubah} baris dikonversi.");
    }

    /**
     * Migrasi itu memanggil dropUnique dengan nama index eksplisit. Kalau namanya
     * berbeda di database ini (mis. dibuat manual saat perbaikan darurat),
     * ALTER TABLE ... DROP CONSTRAINT akan gagal dan menghentikan deploy.
     *
     * @param  list<string>  $tertunda
     */
    private function periksaUniqueInventaris(array $tertunda): void
    {
        $migrasi = '2026_07_22_000002_fix_kode_unik_fisik_unique_per_pesantren';

        if (! in_array($migrasi, $tertunda, true) || ! Schema::hasTable('kesantrian_inventaris')) {
            return;
        }

        $nama = 'kesantrian_inventaris_kode_unik_fisik_unique';

        $ada = collect(Schema::getIndexes('kesantrian_inventaris'))
            ->contains(fn (array $index) => $index['name'] === $nama);

        $ada
            ? $this->catat(self::OK, 'Unique kode_unik_fisik', "Index {$nama} ditemukan, siap di-drop.")
            : $this->catat(self::BLOCK, 'Unique kode_unik_fisik', sprintf(
                "Index %s TIDAK ditemukan, padahal migrasi akan men-drop-nya.\n".
                '      Index yang ada: %s',
                $nama,
                collect(Schema::getIndexes('kesantrian_inventaris'))->pluck('name')->implode(', ') ?: '(tidak ada)',
            ));
    }

    /**
     * Migrasi tambalan hanya menyentuh pesantren yang punya NOL baris amal master.
     * Sekadar laporan supaya jumlahnya tidak mengejutkan setelah deploy.
     *
     * @param  list<string>  $tertunda
     */
    private function periksaAmalMaster(array $tertunda): void
    {
        $migrasi = '2026_08_13_000003_seed_amal_master_untuk_pesantren_yang_kosong';

        if (! in_array($migrasi, $tertunda, true) || ! Schema::hasTable('kesantrian_amal_master')) {
            return;
        }

        $sudahPunya = DB::table('kesantrian_amal_master')->distinct()->pluck('pesantren_id');
        $kosong = DB::table('pesantrens')->whereNotIn('id', $sudahPunya)->count();

        if ($kosong === 0) {
            $this->catat(self::OK, 'Amalan bawaan', 'Semua pesantren sudah punya amal master.');

            return;
        }

        $this->catat(self::WARN, 'Amalan bawaan', sprintf(
            "%d pesantren akan diisikan 7 amalan bawaan oleh migrasi tambalan.\n".
            '      Pastikan tidak ada yang SENGAJA mengosongkan daftarnya.',
            $kosong,
        ));
    }

    private function catat(string $status, string $judul, string $pesan): void
    {
        $this->hasil[] = compact('status', 'judul', 'pesan');
    }

    private function laporkan(): int
    {
        $this->line('<comment>Hasil pemeriksaan:</comment>');

        foreach ($this->hasil as $h) {
            $tanda = match ($h['status']) {
                self::BLOCK => '<fg=red>[BLOCK]</>',
                self::WARN => '<fg=yellow>[WARN ]</>',
                default => '<fg=green>[ OK  ]</>',
            };

            $this->line("  {$tanda} {$h['judul']}: {$h['pesan']}");
        }

        $this->newLine();

        $blokir = collect($this->hasil)->where('status', self::BLOCK)->count();

        if ($blokir > 0) {
            $this->error("{$blokir} masalah menghalangi deploy. JANGAN merge ke main sebelum beres.");

            return self::FAILURE;
        }

        $this->info('Aman — migrasi yang tertunda tidak akan menabrak data yang ada.');

        return self::SUCCESS;
    }
}
