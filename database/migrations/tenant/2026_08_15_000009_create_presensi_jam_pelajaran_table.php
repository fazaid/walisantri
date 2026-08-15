<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Master jam pelajaran per pesantren — tabel master, BUKAN CHECK constraint:
// pembagian jam berbeda antar pesantren, dan mengubahnya tidak boleh butuh migrasi.
//
// ⚠️ BUKAN jadwal mingguan. Tidak ada kolom hari, kelas, atau mapel di sini. Yang
// disimpan hanya "jam ke-3 itu pukul berapa"; kombinasi (kelas, mapel, jam ke-N,
// tanggal) ditentukan saat pengisian. Jadwal mingguan penuh punya konsekuensinya
// sendiri (deteksi bentrok, jadwal per semester, hari libur yang menggeser) dan
// sengaja ditunda sampai ada pesantren yang benar-benar memintanya (§21).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_jam_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();

            // 1..N. Nilai 0 tidak pernah dipakai di sini — di tabel `presensi` ia
            // sudah bermakna "presensi harian" (Presensi::HARIAN).
            $table->smallInteger('jam_ke');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('label', 50)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['pesantren_id', 'jam_ke'], 'presensi_jam_unik_ps_ke');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_jam_pelajaran');
    }
};
