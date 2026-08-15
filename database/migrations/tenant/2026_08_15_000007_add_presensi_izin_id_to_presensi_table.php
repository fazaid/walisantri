<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kolom ini sengaja TIDAK ikut lahir bersama tabel presensi (2026_08_15_000003):
// ia FK ke presensi_izin yang baru ada di fase ini, dan FK ke tabel yang belum
// ada tidak bisa ditulis. Dicatat di PRD §3.2 sebagai pergeseran yang disengaja.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->foreignId('presensi_izin_id')->nullable()->after('sumber')
                ->constrained('presensi_izin')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('presensi_izin_id');
        });
    }
};
