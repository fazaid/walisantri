<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Wali kelas adalah penugasan, bukan role: satu ustadz bisa merangkap pembimbing,
// pengampu, dan wali kelas sekaligus — jadi disimpan sebagai FK di entitas yang
// ditugaskan, bukan sebagai nilai baru di users.role. Fondasi untuk modul absensi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('wali_kelas_id')
                ->nullable()
                ->after('nama_kelas')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('wali_kelas_id');
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropIndex(['wali_kelas_id']);
            $table->dropConstrainedForeignId('wali_kelas_id');
        });
    }
};
