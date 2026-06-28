<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        // PostgreSQL: enum diimplementasikan sebagai varchar + CHECK constraint
        DB::statement("ALTER TABLE pesantrens DROP CONSTRAINT IF EXISTS pesantrens_paket_langganan_check");
        DB::statement("ALTER TABLE pesantrens ADD CONSTRAINT pesantrens_paket_langganan_check CHECK (paket_langganan IN ('rintisan', 'tumbuh', 'berkembang', 'maju'))");
        DB::statement("ALTER TABLE pesantrens ALTER COLUMN paket_langganan SET DEFAULT 'rintisan'");
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE pesantrens DROP CONSTRAINT IF EXISTS pesantrens_paket_langganan_check");
        DB::statement("ALTER TABLE pesantrens ADD CONSTRAINT pesantrens_paket_langganan_check CHECK (paket_langganan IN ('gratis', 'rintisan', 'berkembang', 'maju'))");
        DB::statement("ALTER TABLE pesantrens ALTER COLUMN paket_langganan SET DEFAULT 'gratis'");
    }
};
