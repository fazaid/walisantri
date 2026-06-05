<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan_spp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('santri_id')
                ->constrained('santri')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('bulan');  // 1–12
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('nominal');    // dalam rupiah
            $table->date('jatuh_tempo')->nullable();
            $table->string('keterangan')->nullable()->default('SPP Bulanan');
            $table->enum('status', ['belum_bayar', 'lunas'])->default('belum_bayar');
            $table->timestamps();

            // Satu tagihan per santri per bulan per tahun
            $table->unique(['pesantren_id', 'santri_id', 'bulan', 'tahun'], 'tagihan_spp_unik_per_bulan');
            $table->index(['pesantren_id', 'bulan', 'tahun'], 'tagihan_spp_periode_idx');
            $table->index(['pesantren_id', 'santri_id'], 'tagihan_spp_santri_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_spp');
    }
};
