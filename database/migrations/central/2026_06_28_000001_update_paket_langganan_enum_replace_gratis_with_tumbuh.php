<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LAMA = "'gratis', 'rintisan', 'berkembang', 'maju'";

    private const BARU = "'rintisan', 'tumbuh', 'berkembang', 'maju'";

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->tulisUlangCheckSqlite(self::LAMA, self::BARU, 'gratis', 'rintisan');

            return;
        }

        // PostgreSQL: enum diimplementasikan sebagai varchar + CHECK constraint
        DB::statement('ALTER TABLE pesantrens DROP CONSTRAINT IF EXISTS pesantrens_paket_langganan_check');
        DB::statement('ALTER TABLE pesantrens ADD CONSTRAINT pesantrens_paket_langganan_check CHECK (paket_langganan IN ('.self::BARU.'))');
        DB::statement("ALTER TABLE pesantrens ALTER COLUMN paket_langganan SET DEFAULT 'rintisan'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->tulisUlangCheckSqlite(self::BARU, self::LAMA, 'rintisan', 'gratis');

            return;
        }

        DB::statement('ALTER TABLE pesantrens DROP CONSTRAINT IF EXISTS pesantrens_paket_langganan_check');
        DB::statement('ALTER TABLE pesantrens ADD CONSTRAINT pesantrens_paket_langganan_check CHECK (paket_langganan IN ('.self::LAMA.'))');
        DB::statement("ALTER TABLE pesantrens ALTER COLUMN paket_langganan SET DEFAULT 'gratis'");
    }

    /**
     * Semula cabang SQLite cuma `return`, dengan asumsi basis data test tidak
     * perlu ikut akurat. Asumsi itu keliru: seluruh suite lokal jadi tidak bisa
     * membuat pesantren paket Tumbuh sama sekali, sehingga paket yang paling
     * populer justru satu-satunya yang tak pernah tersentuh tes — persis celah
     * yang membuat bug `orders.paket_target` lolos sekian lama.
     *
     * SQLite tidak punya ALTER CONSTRAINT, jadi definisi tabel di sqlite_master
     * ditulis ulang langsung (pola sama seperti `tenant/2026_07_25_000001`).
     */
    private function tulisUlangCheckSqlite(string $dari, string $ke, string $defaultLama, string $defaultBaru): void
    {
        DB::statement('PRAGMA writable_schema = ON');

        DB::statement(
            'UPDATE sqlite_master SET sql = replace(sql, ?, ?) WHERE type = ? AND name = ?',
            ["check (\"paket_langganan\" in ({$dari}))", "check (\"paket_langganan\" in ({$ke}))", 'table', 'pesantrens'],
        );

        DB::statement(
            'UPDATE sqlite_master SET sql = replace(sql, ?, ?) WHERE type = ? AND name = ?',
            ["not null default '{$defaultLama}'", "not null default '{$defaultBaru}'", 'table', 'pesantrens'],
        );

        // Versi skema harus dinaikkan manual, kalau tidak koneksi yang sedang
        // terbuka masih memakai definisi tabel yang lama.
        $versi = (int) DB::selectOne('PRAGMA schema_version')->schema_version;
        DB::statement('PRAGMA schema_version = '.($versi + 1));
        DB::statement('PRAGMA writable_schema = OFF');
    }
};
