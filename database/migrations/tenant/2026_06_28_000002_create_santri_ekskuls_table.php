<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('santri_ekskuls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pesantren_id');
            $table->foreignId('santri_id')->constrained('santri')->cascadeOnDelete();
            $table->foreignId('ekskul_id')->constrained('ekskul_masters')->cascadeOnDelete();
            $table->enum('level', ['pemula', 'menengah', 'mahir'])->default('pemula');
            $table->date('tanggal_mulai');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->unique(['santri_id', 'ekskul_id']);
            $table->index('pesantren_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('santri_ekskuls');
    }
};
