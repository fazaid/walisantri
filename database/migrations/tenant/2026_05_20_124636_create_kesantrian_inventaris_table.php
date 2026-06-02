<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kesantrian_inventaris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('santri_id')
                ->constrained('santri')
                ->cascadeOnDelete();
            $table->string('nama_barang_umum');
            $table->string('kode_unik_fisik', 30)->unique(); // format: FZ-SRG-01
            $table->unsignedSmallInteger('kuota_regulasi_maksimal');
            $table->enum('kondisi_barang', ['Baik', 'Layak_Rusak', 'Hilang'])
                ->default('Baik');
            $table->date('tanggal_sidak_terakhir')->nullable();
            $table->timestamps();

            $table->index(['pesantren_id', 'santri_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kesantrian_inventaris');
    }
};
