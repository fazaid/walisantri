<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kembalikan index yang hilang saat kelas & kamar berubah dari teks jadi FK.
 *
 * `2026_06_05_000003` men-drop index (pesantren_id, kelas) dan (pesantren_id,
 * kamar) sebelum membuang kolom teksnya, tapi hanya membangunnya kembali di
 * `down()` — kolom `kelas_id`/`kamar_id` yang menggantikannya tidak pernah
 * mendapat index apa pun.
 *
 * Mudah terlewat karena di MySQL foreign key otomatis membuat index, sedangkan
 * PostgreSQL tidak. Aturan wajib §1.7 poin 3 (composite index selalu diawali
 * `pesantren_id`) jadi tidak ditegakkan pada tabel yang paling sering di-query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            // Filter Kelas & Kamar di daftar Santri (SelectFilter di SantrisTable)
            // selalu berjalan di atas global scope pesantren_id, jadi kolom tenant
            // harus jadi kolom pertama.
            $table->index(['pesantren_id', 'kelas_id'], 'santri_tenant_kelas_idx');
            $table->index(['pesantren_id', 'kamar_id'], 'santri_tenant_kamar_idx');
        });
    }

    public function down(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            $table->dropIndex('santri_tenant_kelas_idx');
            $table->dropIndex('santri_tenant_kamar_idx');
        });
    }
};
