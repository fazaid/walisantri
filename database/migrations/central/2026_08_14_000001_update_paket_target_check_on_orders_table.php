<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Luruskan daftar paket yang boleh dipesan.
 *
 * `orders` dibuat 2026-06-02 dengan enum('gratis','rintisan','berkembang','maju').
 * Dua pekan kemudian model bisnis berubah: paket Gratis dihapus dan paket Tumbuh
 * ditambahkan. Tabel `pesantrens` ikut diperbarui lewat `2026_06_28_000001`, tapi
 * `orders` terlewat — dan tidak ada yang menyadarinya karena §3.1 PRD memang tidak
 * pernah mendokumentasikan tabel ini.
 *
 * Akibatnya `UpgradePage` menawarkan Tumbuh (pilihannya dibangun dari
 * `PaketLangganan::cases()`) tapi `UpgradeOrderService::createOrder()` selalu
 * ditolak CHECK constraint saat menyimpannya. Paket yang menurut §5.1 paling
 * populer justru satu-satunya yang mustahil dibeli.
 */
return new class extends Migration
{
    private const LAMA = "'gratis', 'rintisan', 'berkembang', 'maju'";

    private const BARU = "'rintisan', 'tumbuh', 'berkembang', 'maju'";

    public function up(): void
    {
        $this->tulisUlangCheck(self::LAMA, self::BARU);
    }

    public function down(): void
    {
        $this->tulisUlangCheck(self::BARU, self::LAMA);
    }

    private function tulisUlangCheck(string $dari, string $ke): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->tulisUlangCheckSqlite($dari, $ke);

            return;
        }

        // PostgreSQL: enum diimplementasikan sebagai varchar + CHECK constraint.
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_paket_target_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_paket_target_check CHECK (paket_target IN ({$ke}))");
    }

    /**
     * SQLite tidak punya ALTER CONSTRAINT, jadi definisi tabel di sqlite_master
     * ditulis ulang langsung. Tanpa ini basis data test tertinggal di daftar lama
     * dan order paket Tumbuh mustahil diuji secara lokal, padahal production
     * (Postgres) sudah menerimanya. Pola ini mengikuti migrasi
     * `tenant/2026_07_25_000001` yang menghadapi persoalan sama.
     */
    private function tulisUlangCheckSqlite(string $dari, string $ke): void
    {
        DB::statement('PRAGMA writable_schema = ON');

        DB::statement(
            'UPDATE sqlite_master SET sql = replace(sql, ?, ?) WHERE type = ? AND name = ?',
            ["check (\"paket_target\" in ({$dari}))", "check (\"paket_target\" in ({$ke}))", 'table', 'orders'],
        );

        // Versi skema harus dinaikkan manual, kalau tidak koneksi yang sedang
        // terbuka masih memakai definisi tabel yang lama.
        $versi = (int) DB::selectOne('PRAGMA schema_version')->schema_version;
        DB::statement('PRAGMA schema_version = '.($versi + 1));
        DB::statement('PRAGMA writable_schema = OFF');
    }
};
