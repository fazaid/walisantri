<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->string('nama_kelas', 100);
            $table->timestamps();

            $table->unique(['pesantren_id', 'nama_kelas']);
            $table->index('pesantren_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
