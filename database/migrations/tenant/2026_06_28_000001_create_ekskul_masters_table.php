<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekskul_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pesantren_id');
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->string('pengajar')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index('pesantren_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekskul_masters');
    }
};
