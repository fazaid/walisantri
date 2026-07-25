<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE kesantrian_karakter_rapor DROP CONSTRAINT IF EXISTS kesantrian_karakter_rapor_periode_check');
        DB::statement("ALTER TABLE kesantrian_karakter_rapor ADD CONSTRAINT kesantrian_karakter_rapor_periode_check CHECK (periode IN ('Bulanan','Semester_Ganjil','Semester_Genap'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE kesantrian_karakter_rapor DROP CONSTRAINT IF EXISTS kesantrian_karakter_rapor_periode_check');
        DB::statement("ALTER TABLE kesantrian_karakter_rapor ADD CONSTRAINT kesantrian_karakter_rapor_periode_check CHECK (periode IN ('Bulanan','Semester'))");
    }
};
