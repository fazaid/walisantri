<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kupons', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->enum('tipe_diskon', ['nominal', 'persentase']);
            $table->unsignedInteger('nilai_diskon');
            $table->unsignedInteger('min_durasi_bulan')->nullable();
            $table->unsignedInteger('max_penggunaan')->nullable();
            $table->unsignedInteger('jumlah_dipakai')->default(0);
            $table->timestamp('berlaku_hingga')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kupons');
    }
};
