<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->tulisUlangCheckSqlite(
                "'Bulanan', 'Semester'",
                "'Bulanan', 'Semester_Ganjil', 'Semester_Genap'",
            );

            return;
        }

        DB::statement('ALTER TABLE kesantrian_karakter_rapor DROP CONSTRAINT IF EXISTS kesantrian_karakter_rapor_periode_check');
        DB::statement("ALTER TABLE kesantrian_karakter_rapor ADD CONSTRAINT kesantrian_karakter_rapor_periode_check CHECK (periode IN ('Bulanan','Semester_Ganjil','Semester_Genap'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->tulisUlangCheckSqlite(
                "'Bulanan', 'Semester_Ganjil', 'Semester_Genap'",
                "'Bulanan', 'Semester'",
            );

            return;
        }

        DB::statement('ALTER TABLE kesantrian_karakter_rapor DROP CONSTRAINT IF EXISTS kesantrian_karakter_rapor_periode_check');
        DB::statement("ALTER TABLE kesantrian_karakter_rapor ADD CONSTRAINT kesantrian_karakter_rapor_periode_check CHECK (periode IN ('Bulanan','Semester'))");
    }

    /**
     * SQLite tidak punya ALTER CONSTRAINT, jadi definisi tabel di sqlite_master
     * ditulis ulang langsung. Tanpa ini basis data test tertinggal di enum lama
     * dan periode Semester_Ganjil/Genap mustahil diuji, padahal production
     * (Postgres) sudah menerimanya.
     */
    private function tulisUlangCheckSqlite(string $dari, string $ke): void
    {
        DB::statement('PRAGMA writable_schema = ON');

        DB::statement(
            'UPDATE sqlite_master SET sql = replace(sql, ?, ?) WHERE type = ? AND name = ?',
            ["check (\"periode\" in ({$dari}))", "check (\"periode\" in ({$ke}))", 'table', 'kesantrian_karakter_rapor'],
        );

        // Versi skema harus dinaikkan manual, kalau tidak koneksi yang sedang
        // terbuka masih memakai definisi tabel yang lama.
        $versi = (int) DB::selectOne('PRAGMA schema_version')->schema_version;
        DB::statement('PRAGMA schema_version = '.($versi + 1));
        DB::statement('PRAGMA writable_schema = OFF');
    }
};
