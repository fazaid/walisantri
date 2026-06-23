<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tahfidz_ujian');
    }

    public function down(): void
    {
        Schema::create('tahfidz_ujian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('santri_id')
                ->constrained('santri')
                ->cascadeOnDelete();
            $table->foreignId('penguji_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->date('tanggal_ujian');
            $table->enum('target_juz', ['1', '3', '5', '10', '15', '20', '25', '30']);
            $table->enum('status_kelulusan', ['Lulus', 'Mengulang']);
            $table->text('catatan_ujian')->nullable();
            $table->timestamps();

            $table->index(['pesantren_id', 'santri_id', 'tanggal_ujian']);
        });
    }
};
