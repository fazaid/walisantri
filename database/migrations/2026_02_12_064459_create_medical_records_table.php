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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            // Menghubungkan riwayat sakit ke santri
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('nama_penyakit');
            $table->date('tanggal_sakit');
            $table->string('tindakan')->nullable(); // Misal: Rawat inap, istirahat di asrama
            $table->text('catatan_medis')->nullable(); // Misal: Alergi obat paracetamol
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
