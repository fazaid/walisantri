<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kesantrian_mutabaah_rapor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')->constrained('pesantrens')->cascadeOnDelete();
            $table->foreignId('santri_id')->constrained('santri')->cascadeOnDelete();
            $table->unsignedTinyInteger('bulan');
            $table->string('tahun', 4);
            $table->unsignedSmallInteger('total_hari_input')->default(0);
            $table->unsignedSmallInteger('total_hari_udzur')->default(0);
            $table->jsonb('udzur_detail')->default('{}');
            $table->jsonb('ringkasan_amalan')->default('{}');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['santri_id', 'bulan', 'tahun']);
            $table->index(['pesantren_id', 'tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kesantrian_mutabaah_rapor');
    }
};
