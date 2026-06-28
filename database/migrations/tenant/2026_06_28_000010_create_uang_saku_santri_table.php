<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uang_saku_santri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')->constrained()->cascadeOnDelete();
            $table->foreignId('santri_id')->constrained('santri')->cascadeOnDelete();
            $table->string('jenis'); // setoran | pengambilan
            $table->unsignedInteger('nominal');
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('dicatat_oleh')->nullable();
            $table->timestamps();

            $table->index(['pesantren_id', 'santri_id']);
            $table->index(['pesantren_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uang_saku_santri');
    }
};
