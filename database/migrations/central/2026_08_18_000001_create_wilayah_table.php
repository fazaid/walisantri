<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi wilayah administratif Kemendagri (§3.1) — provinsi s.d. desa/kelurahan,
 * dipakai kolom wilayah berjenjang di halaman /register (§4.1) dan PesantrenSettingsPage.
 *
 * Tabel CENTRAL dan read-only bagi aplikasi: satu-satunya penulisnya adalah
 * `php artisan wilayah:impor`.
 *
 * ISINYA SENGAJA TIDAK DISEMAI DI SINI. Migrasi hanya jalan sekali di production,
 * sehingga setiap rilis dataset Kemendagri berikutnya akan menuntut migrasi kesekian
 * — dan `migrate:refresh` di production mustahil. Pemuat idempoten menyelesaikan itu
 * selamanya; lihat ImporWilayahCommand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            // '32' provinsi | '32.01' kab/kota | '32.01.01' kecamatan | '32.01.01.2001' desa
            $table->string('kode', 13)->primary();
            $table->string('nama', 100);

            // Disimpan EKSPLISIT, bukan diturunkan lewat LIKE 'kode.%'. Dua sebabnya:
            // (1) pada kolasi non-C (default en_US.UTF-8) index btree biasa TIDAK dipakai
            //     Postgres untuk LIKE 'prefix%' — butuh varchar_pattern_ops, mudah lupa,
            //     dan gagalnya senyap: seq scan 91 ribu baris;
            // (2) LIKE '32.01.%' ikut menjaring cucu ('32.01.01.2001'), jadi tetap butuh
            //     pagar panjang kode.
            // Kesetaraan `parent_kode = ?` bebas dari keduanya dan berperilaku identik di
            // SQLite maupun PostgreSQL.
            //
            // Provinsi ber-parent NULL (bukan string kosong): dengan begitu satu index yang
            // sama melayani "anak dari X" maupun "daftar provinsi", karena btree Postgres
            // ikut mengindeks NULL.
            $table->string('parent_kode', 13)->nullable();

            // Bisa diturunkan dari panjang kode, tapi disimpan agar query tetap terbaca
            // ("ambil semua desa", "pastikan kode ini level 4") tanpa aritmetika string.
            // Sengaja TANPA CHECK constraint meski konvensi §3 memakainya untuk enum:
            // kolom ini tidak punya satu pun jalur tulis dari pengguna.
            $table->smallInteger('level');

            // Tanpa timestamps: data referensi statis yang diganti wholesale.
            $table->index(['parent_kode', 'nama'], 'wilayah_parent_nama_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
