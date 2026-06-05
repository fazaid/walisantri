<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestasi_santri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')->constrained('pesantrens')->cascadeOnDelete();
            $table->foreignId('santri_id')->constrained('santri')->cascadeOnDelete();
            $table->string('judul');
            $table->string('kategori');
            $table->enum('tingkat', ['internal', 'kabupaten', 'provinsi', 'nasional', 'internasional']);
            $table->string('posisi')->nullable();
            $table->date('tanggal');
            $table->string('penyelenggara')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('dokumen')->nullable();
            $table->timestamps();

            $table->index(['pesantren_id', 'santri_id'], 'prestasi_santri_idx');
            $table->index(['pesantren_id', 'tingkat'], 'prestasi_tingkat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestasi_santri');
    }
};
