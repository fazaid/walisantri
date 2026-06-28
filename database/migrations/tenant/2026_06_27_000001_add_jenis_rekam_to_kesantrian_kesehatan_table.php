<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kesantrian_kesehatan', function (Blueprint $table) {
            $table->string('jenis_rekam', 10)->default('keluhan')->after('santri_id');
        });

        // SQLite (test in-memory) tidak perlu — hanya enforce di PostgreSQL (production/CI)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE kesantrian_kesehatan ALTER COLUMN kategori_keluhan DROP NOT NULL');
            DB::statement('ALTER TABLE kesantrian_kesehatan ALTER COLUMN tindakan_dan_obat DROP NOT NULL');
            DB::statement('ALTER TABLE kesantrian_kesehatan ALTER COLUMN status_pemulihan DROP NOT NULL');
        }
    }

    public function down(): void
    {
        // Isi default sebelum NOT NULL dikembalikan
        DB::table('kesantrian_kesehatan')
            ->whereNull('kategori_keluhan')
            ->update(['kategori_keluhan' => 'Lainnya']);
        DB::table('kesantrian_kesehatan')
            ->whereNull('tindakan_dan_obat')
            ->update(['tindakan_dan_obat' => '-']);
        DB::table('kesantrian_kesehatan')
            ->whereNull('status_pemulihan')
            ->update(['status_pemulihan' => 'Rawat_Mandiri']);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE kesantrian_kesehatan ALTER COLUMN kategori_keluhan SET NOT NULL');
            DB::statement('ALTER TABLE kesantrian_kesehatan ALTER COLUMN tindakan_dan_obat SET NOT NULL');
            DB::statement('ALTER TABLE kesantrian_kesehatan ALTER COLUMN status_pemulihan SET NOT NULL');
        }

        Schema::table('kesantrian_kesehatan', function (Blueprint $table) {
            $table->dropColumn('jenis_rekam');
        });
    }
};
