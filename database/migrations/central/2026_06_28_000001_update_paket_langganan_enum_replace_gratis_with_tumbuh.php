<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Data migration sudah dilakukan di migrate_gratis_tenants_to_rintisan
        // Sekarang update definisi kolom enum
        if (DB::getDriverName() === 'sqlite') {
            // SQLite tidak support ALTER COLUMN untuk enum; skip (test env)
            return;
        }

        DB::statement("
            ALTER TABLE pesantrens
            MODIFY COLUMN paket_langganan
            ENUM('rintisan', 'tumbuh', 'berkembang', 'maju')
            NOT NULL DEFAULT 'rintisan'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE pesantrens
            MODIFY COLUMN paket_langganan
            ENUM('gratis', 'rintisan', 'berkembang', 'maju')
            NOT NULL DEFAULT 'gratis'
        ");
    }
};
