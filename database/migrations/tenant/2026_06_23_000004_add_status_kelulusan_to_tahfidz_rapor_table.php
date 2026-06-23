<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahfidz_rapor', function (Blueprint $table) {
            $table->enum('status_kelulusan', ['Lulus', 'Mengulang'])->nullable()->after('target_juz');
        });
    }

    public function down(): void
    {
        Schema::table('tahfidz_rapor', function (Blueprint $table) {
            $table->dropColumn('status_kelulusan');
        });
    }
};
