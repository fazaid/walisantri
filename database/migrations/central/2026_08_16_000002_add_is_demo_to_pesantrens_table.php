<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menandai tenant sandbox publik (demo.walisantri.com) supaya tidak pernah
 * terhitung sebagai pelanggan di dashboard super admin.
 *
 * Sengaja kolom boolean aditif, BUKAN nilai baru pada enum paket_langganan /
 * status_berlangganan: keduanya dijaga CHECK constraint, dan mengubah CHECK
 * menuntut cabang khusus SQLite (lihat central/2026_06_28_000001). ALTER TABLE
 * ADD COLUMN tidak menyentuh constraint mana pun, jadi jalannya sama di kedua
 * driver.
 *
 * Tanpa index: nilainya hampir selalu false, sedangkan query yang memakainya
 * justru menyaring false — index tidak menolong apa pun di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesantrens', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('pesantrens', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};
