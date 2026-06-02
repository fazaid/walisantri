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
        Schema::create('kesantrian_mutabaah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('santri_id')
                ->constrained('santri')
                ->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedTinyInteger('jamaah_5_waktu')->default(5);
            $table->boolean('is_rawatib')->default(false);
            $table->boolean('is_shalat_malam')->default(false);
            $table->boolean('is_dhuha')->default(false);
            $table->boolean('is_tilawah_1juz')->default(false);
            $table->boolean('is_infak')->default(false);
            $table->boolean('is_puasa')->default(false);
            $table->enum('status_udzur', [
                'Tidak',
                'Sakit',
                'Haid',
                'Izin_Pulang',
                'Tugas_Pondok',
            ])->default('Tidak');
            $table->timestamps();

            $table->index(['pesantren_id', 'santri_id', 'tanggal']);
            // Satu baris mutabaah per santri per hari
            $table->unique(['santri_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kesantrian_mutabaah');
    }
};
