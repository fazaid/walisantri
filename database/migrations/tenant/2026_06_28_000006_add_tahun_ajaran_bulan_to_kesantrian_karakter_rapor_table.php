<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kesantrian_karakter_rapor', function (Blueprint $table) {
            $table->string('tahun_ajaran', 9)->nullable()->after('periode');
            $table->string('bulan', 10)->nullable()->after('tahun_ajaran');
        });
    }

    public function down(): void
    {
        Schema::table('kesantrian_karakter_rapor', function (Blueprint $table) {
            $table->dropColumn(['tahun_ajaran', 'bulan']);
        });
    }
};
