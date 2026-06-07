<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->cascadeOnDelete();
            $table->foreignId('ustadz_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('nama_mapel', 100);
            $table->timestamps();

            $table->unique(['pesantren_id', 'kelas_id', 'nama_mapel']);
            $table->index(['pesantren_id', 'kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mata_pelajaran');
    }
};
