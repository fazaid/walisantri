<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kesantrian_inventaris', function (Blueprint $table) {
            // Sebelumnya kode_unik_fisik unique GLOBAL, padahal aplikasi
            // multi-tenant (single DB) dan form memvalidasi unik PER pesantren.
            // Akibatnya pesantren B gagal menyimpan kode yang sudah dipakai
            // pesantren A (SQLSTATE 23505). Ubah jadi unik per (pesantren_id, kode).
            $table->dropUnique('kesantrian_inventaris_kode_unik_fisik_unique');
            $table->unique(['pesantren_id', 'kode_unik_fisik']);
        });
    }

    public function down(): void
    {
        Schema::table('kesantrian_inventaris', function (Blueprint $table) {
            $table->dropUnique(['pesantren_id', 'kode_unik_fisik']);
            $table->unique('kode_unik_fisik');
        });
    }
};
