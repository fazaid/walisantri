<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahfidz_rapor', function (Blueprint $table) {
            $table->unsignedTinyInteger('target_juz')->nullable()->after('tanggal_ujian');
        });
    }

    public function down(): void
    {
        Schema::table('tahfidz_rapor', function (Blueprint $table) {
            $table->dropColumn('target_juz');
        });
    }
};
