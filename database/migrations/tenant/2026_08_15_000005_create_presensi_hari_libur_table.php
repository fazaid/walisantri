<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SATU BARIS PER HARI, bukan rentang (tanggal_mulai/tanggal_selesai).
//
// Form tetap menerima rentang lalu mengembangkannya di sini. Libur Ramadan ≈30
// baris — murah — dan sebagai imbalannya rekap cukup whereIn('tanggal', …) alih-alih
// logika tumpang-tindih rentang, yang selalu salah di kasus tepi (rentang yang
// beririsan sebagian, rentang di dalam rentang, batas inklusif vs eksklusif).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_hari_libur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();

            $table->date('tanggal');
            $table->string('keterangan', 150);
            $table->string('tahun_ajaran', 10); // format: "2026/2027"
            $table->timestamps();

            // Satu keterangan libur per tanggal per pesantren. Menyimpan rentang yang
            // beririsan dengan yang sudah ada akan MEMPERBARUI keterangannya, bukan
            // gagal — lihat PresensiHariLiburResource::simpanRentang().
            $table->unique(['pesantren_id', 'tanggal'], 'presensi_libur_unik_ps_tgl');
            $table->index(['pesantren_id', 'tahun_ajaran'], 'idx_libur_ps_ta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_hari_libur');
    }
};
