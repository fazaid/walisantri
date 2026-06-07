<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_akademik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('santri_id')
                ->constrained('santri')
                ->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')
                ->constrained('mata_pelajaran')
                ->cascadeOnDelete();
            $table->string('tahun_ajaran', 10);         // format: "2026/2027"
            $table->enum('periode', [
                'Bulanan',
                'Semester_Ganjil',
                'Semester_Genap',
            ]);
            $table->unsignedTinyInteger('nilai');        // nilai tunggal 0-100
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['pesantren_id', 'santri_id', 'tahun_ajaran', 'periode']);
            // Satu nilai per santri per mapel per periode per tahun ajaran
            $table->unique(['santri_id', 'mata_pelajaran_id', 'tahun_ajaran', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_akademik');
    }
};
