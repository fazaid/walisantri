<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('tanggal_masuk')->nullable();
            $table->string('diterima_di_kelas')->nullable(); // Contoh: 7A MTS
            $table->string('status_aktif')->default('Aktif'); // Aktif, Lulus, Mutasi, Keluar
            $table->string('kelas_saat_ini')->nullable(); // Contoh: 8A MTS
            $table->string('wali_kelas')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['tanggal_masuk', 'diterima_di_kelas', 'status_aktif', 'kelas_saat_ini', 'wali_kelas']);
        });
    }
};
