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
    Schema::create('tahfidz_progress', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pesantren_id')
              ->constrained('pesantrens')
              ->cascadeOnDelete();
        $table->foreignId('santri_id')
              ->constrained('santri')
              ->cascadeOnDelete();
        $table->foreignId('ustadz_id')
              ->constrained('users')
              ->restrictOnDelete();
        $table->date('tanggal');
        $table->enum('tipe_setoran', ['Sabaq', 'Sabqi', 'Manzil']);
        $table->string('nama_surah', 100);
        $table->unsignedSmallInteger('ayat_mulai');
        $table->unsignedSmallInteger('ayat_selesai');
        $table->enum('nilai_kelancaran', [
            'Mumtaz',
            'Jayyid Jiddan',
            'Jayyid',
            'Maqbul',
        ]);
        $table->text('catatan_evaluasi')->nullable();
        $table->timestamps();

        $table->index(['pesantren_id', 'santri_id', 'tanggal']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahfidz_progress');
    }
};
