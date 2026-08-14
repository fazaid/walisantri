<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tandai staf yang sudah ada sebagai terverifikasi.
 *
 * Verifikasi email diperkenalkan setelah 16 pesantren berjalan, dan seluruh
 * user yang ada bernilai `email_verified_at = NULL`. Tanpa tambalan ini mereka
 * semua akan disambut spanduk "konfirmasi alamat email Anda" padahal alamatnya
 * jelas hidup — merekalah yang selama ini memakai sistemnya.
 *
 * Hanya `admin_pesantren` dan `super_admin` yang disentuh. Ustadz & wali santri
 * sengaja dibiarkan NULL: alamat mereka diketik admin, bukan diri sendiri, dan
 * belum ada satu pun email yang menyasar mereka (§12.2). Menandai mereka
 * terverifikasi berarti berbohong tentang sesuatu yang mungkin dipakai saat
 * email untuk ustadz kelak ditambahkan.
 *
 * Ditulis lewat query builder, bukan Eloquent: `email_verified_at` tidak
 * terdaftar di atribut #[Fillable] model User, jadi mass-assign biasa akan
 * diam-diam tidak mengisi apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', ['admin_pesantren', 'super_admin'])
            ->whereNotNull('email')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Tidak dibalik: begitu kolomnya terisi, tidak ada lagi cara membedakan
        // baris hasil tambalan ini dari yang benar-benar diverifikasi manusia.
        // Mengosongkannya kembali berarti menghapus bukti verifikasi asli.
    }
};
