<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\Santri;
use App\Support\AmalanDefault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeployPreflightTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRASI_PERIODE = '2026_07_25_000001_update_periode_check_on_kesantrian_karakter_rapor_table';

    private const MIGRASI_AMAL = '2026_08_13_000003_seed_amal_master_untuk_pesantren_yang_kosong';

    /** Buat satu migrasi tampak belum jalan, supaya pemeriksanya aktif. */
    private function jadikanTertunda(string $migrasi): void
    {
        DB::table('migrations')->where('migration', $migrasi)->delete();
    }

    /**
     * CHECK constraint di DB test sudah versi baru, jadi baris berperiode 'Semester'
     * harus diselundupkan melewatinya. Caranya berbeda per driver: SQLite punya
     * PRAGMA, PostgreSQL harus melepas constraint-nya sementara. Tanpa cabang ini
     * tes lulus di SQLite lokal tapi gagal di CI yang memakai PostgreSQL.
     */
    private function sisipkanKarakterPeriodeLama(Pesantren $pesantren, string $tanggal): void
    {
        $this->setelCheckPeriode(false);

        DB::table('kesantrian_karakter_rapor')->insert([
            'pesantren_id' => $pesantren->id,
            'santri_id' => Santri::factory()->create(['pesantren_id' => $pesantren->id])->id,
            'periode' => 'Semester',
            'tanggal_input' => $tanggal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->setelCheckPeriode(true);
    }

    private function setelCheckPeriode(bool $aktif): void
    {
        $constraint = 'kesantrian_karakter_rapor_periode_check';

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA ignore_check_constraints = '.($aktif ? 'OFF' : 'ON'));

            return;
        }

        DB::statement($aktif
            ? "ALTER TABLE kesantrian_karakter_rapor ADD CONSTRAINT {$constraint} CHECK (periode IN ('Bulanan','Semester_Ganjil','Semester_Genap','Semester'))"
            : "ALTER TABLE kesantrian_karakter_rapor DROP CONSTRAINT IF EXISTS {$constraint}");
    }

    public function test_lolos_saat_tidak_ada_migrasi_tertunda(): void
    {
        $this->artisan('deploy:preflight')
            ->expectsOutputToContain('Tidak ada migrasi tertunda')
            ->assertSuccessful();
    }

    public function test_memblokir_saat_masih_ada_periode_semester_lama(): void
    {
        // Inilah yang akan menggagalkan `migrate --force` di production:
        // PostgreSQL memvalidasi baris lama saat ADD CONSTRAINT CHECK.
        $pesantren = Pesantren::factory()->create();
        $this->sisipkanKarakterPeriodeLama($pesantren, '2026-11-10');
        $this->jadikanTertunda(self::MIGRASI_PERIODE);

        $this->artisan('deploy:preflight')
            ->expectsOutputToContain('[BLOCK]')
            ->expectsOutputToContain('menghalangi deploy')
            ->assertFailed();
    }

    public function test_lolos_saat_migrasi_periode_tertunda_tapi_datanya_bersih(): void
    {
        Pesantren::factory()->create();
        $this->jadikanTertunda(self::MIGRASI_PERIODE);

        $this->artisan('deploy:preflight')
            ->expectsOutputToContain('Tidak ada baris periode "Semester"')
            ->assertSuccessful();
    }

    public function test_perbaiki_periode_memetakan_semester_sesuai_bulan(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->sisipkanKarakterPeriodeLama($pesantren, '2026-11-10'); // Nov -> Ganjil
        $this->sisipkanKarakterPeriodeLama($pesantren, '2027-02-20'); // Feb -> Genap
        $this->jadikanTertunda(self::MIGRASI_PERIODE);

        $this->artisan('deploy:preflight --perbaiki-periode')
            ->expectsConfirmation('Lanjutkan?', 'yes')
            ->assertSuccessful();

        $periode = DB::table('kesantrian_karakter_rapor')->orderBy('id')->pluck('periode')->all();
        $this->assertSame(['Semester_Ganjil', 'Semester_Genap'], $periode);
    }

    public function test_perbaiki_periode_yang_dibatalkan_tidak_mengubah_apa_pun(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->sisipkanKarakterPeriodeLama($pesantren, '2026-11-10');
        $this->jadikanTertunda(self::MIGRASI_PERIODE);

        $this->artisan('deploy:preflight --perbaiki-periode')
            ->expectsConfirmation('Lanjutkan?', 'no')
            ->assertFailed();

        $this->assertSame(
            'Semester',
            DB::table('kesantrian_karakter_rapor')->value('periode'),
        );
    }

    public function test_melaporkan_pesantren_yang_akan_diisikan_amalan(): void
    {
        Pesantren::factory()->create();                 // kosong
        $terisi = Pesantren::factory()->create();
        AmalanDefault::untukPesantren($terisi->id);

        $this->jadikanTertunda(self::MIGRASI_AMAL);

        // Catatan: expectsOutputToContain mencocokkan per panggilan tulis, bukan ke
        // seluruh buffer — dua substring yang berada di BARIS YANG SAMA tidak bisa
        // keduanya terpenuhi. Karena itu penanda [WARN ] tidak ikut di-assert di sini.
        $this->artisan('deploy:preflight')
            ->expectsOutputToContain('1 pesantren akan diisikan')
            ->assertSuccessful();
    }

    public function test_migrasi_tertunda_ditampilkan_berurutan(): void
    {
        Pesantren::factory()->create();
        $this->jadikanTertunda(self::MIGRASI_AMAL);

        $this->artisan('deploy:preflight')
            ->expectsOutputToContain('1 migrasi akan dijalankan')
            ->expectsOutputToContain(self::MIGRASI_AMAL)
            ->assertSuccessful();
    }
}
