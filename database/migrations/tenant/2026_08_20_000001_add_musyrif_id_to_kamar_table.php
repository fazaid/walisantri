<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Musyrif kamar adalah penugasan, bukan role: satu ustadz bisa merangkap pembimbing,
// wali kelas, dan musyrif sekaligus — jadi disimpan sebagai FK di entitas yang
// ditugaskan, bukan sebagai nilai baru di users.role.
//
// Dinamai `musyrif_id`, BUKAN `wali_kamar_id` seperti yang sempat disebut §3.2 Modul
// Presensi: "musyrif" adalah istilah yang benar-benar dipakai pesantren untuk pengasuh
// kamar, dan menamai kolom dengan istilah yang tidak pernah diucapkan siapa pun hanya
// membuat dokumen dan lapangan berbeda kosakata.
//
// ⚠️ LABEL SAJA. Presedennya ekskul_masters.pembina_id, BUKAN kelas.wali_kelas_id —
// yang terakhir itu menganggur tujuh rilis lalu di v4.25 berubah jadi cakupan presensi
// harian penuh. Kolom ini sengaja tidak membuka cakupan data apa pun (§5.4).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kamar', function (Blueprint $table) {
            $table->foreignId('musyrif_id')
                ->nullable()
                ->after('nama_kamar')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('musyrif_id');
        });
    }

    public function down(): void
    {
        Schema::table('kamar', function (Blueprint $table) {
            $table->dropIndex(['musyrif_id']);
            $table->dropConstrainedForeignId('musyrif_id');
        });
    }
};
