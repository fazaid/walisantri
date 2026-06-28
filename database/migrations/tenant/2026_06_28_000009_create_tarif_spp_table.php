<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif_spp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')->constrained('pesantrens')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->unsignedInteger('nominal');
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['pesantren_id', 'kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_spp');
    }
};
