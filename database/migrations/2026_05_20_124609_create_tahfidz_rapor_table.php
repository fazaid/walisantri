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
        Schema::create('tahfidz_rapor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('santri_id')
                ->constrained('santri')
                ->cascadeOnDelete();
            $table->string('tahun_ajaran', 10);         // format: "2026/2027"
            $table->enum('periode', [
                'Bulanan',
                'Semester_Ganjil',
                'Semester_Genap',
            ]);
            $table->string('nilai_hafalan', 10);         // hasil kalkulasi otomatis sistem
            $table->enum('nilai_tilawah', ['A', 'B', 'C', 'D']);
            $table->enum('nilai_makhraj', ['A', 'B', 'C', 'D']);
            $table->enum('nilai_tajwid',  ['A', 'B', 'C', 'D']);
            $table->text('rekomendasi_pembimbing');
            $table->timestamps();

            $table->index(['pesantren_id', 'santri_id', 'tahun_ajaran', 'periode']);
            // Satu rapor per santri per periode per tahun ajaran
            $table->unique(['santri_id', 'tahun_ajaran', 'periode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahfidz_rapor');
    }
};
