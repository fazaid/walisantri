<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahfidz_progress', function (Blueprint $table) {
            $table->string('nama_surah', 100)->nullable()->change();
            $table->unsignedSmallInteger('halaman_mulai')->nullable()->after('nama_surah');
            $table->unsignedSmallInteger('halaman_selesai')->nullable()->after('halaman_mulai');
            $table->dropColumn(['ayat_mulai', 'ayat_selesai']);
        });
    }

    public function down(): void
    {
        Schema::table('tahfidz_progress', function (Blueprint $table) {
            $table->string('nama_surah', 100)->nullable(false)->change();
            $table->dropColumn(['halaman_mulai', 'halaman_selesai']);
            $table->unsignedSmallInteger('ayat_mulai')->after('nama_surah');
            $table->unsignedSmallInteger('ayat_selesai')->after('ayat_mulai');
        });
    }
};
