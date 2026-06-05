<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kamar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->string('nama_kamar', 100);
            $table->unsignedSmallInteger('kapasitas')->default(0);
            $table->timestamps();

            $table->unique(['pesantren_id', 'nama_kamar']);
            $table->index('pesantren_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kamar');
    }
};
