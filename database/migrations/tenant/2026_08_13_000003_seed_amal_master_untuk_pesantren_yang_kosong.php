<?php

use App\Models\Pesantren;
use App\Support\AmalanDefault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambal pesantren yang tidak pernah kebagian amalan bawaan.
 *
 * Migrasi `2026_06_23_000007` mengisikan 7 amalan default hanya ke pesantren yang
 * ada saat ia dijalankan. Karena migrasi cuma jalan sekali dan alur registrasi
 * belum memanggilnya, setiap pesantren yang mendaftar setelah tanggal itu punya
 * NOL baris amal master — modul Mutaba'ah mereka lumpuh tanpa pesan error apa pun.
 *
 * Kebocoran di sisi registrasi sudah ditutup di `OnboardPesantren` (lihat
 * `App\Support\AmalanDefault`); migrasi ini hanya menyembuhkan yang telanjur.
 *
 * Sengaja HANYA menyentuh pesantren yang benar-benar kosong. Pesantren yang sudah
 * punya daftar — termasuk yang sudah menghapus atau mengubah amalan sesuai
 * kebiasaannya — tidak diutak-atik, supaya kustomisasi mereka tidak dihidupkan
 * ulang tanpa diminta.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sudahPunya = DB::table('kesantrian_amal_master')
            ->distinct()
            ->pluck('pesantren_id');

        Pesantren::whereNotIn('id', $sudahPunya)
            ->pluck('id')
            ->each(fn (int $pesantrenId) => AmalanDefault::untukPesantren($pesantrenId));
    }

    public function down(): void
    {
        // Tidak dibalik: tanpa jejak "baris ini hasil tambalan", rollback berisiko
        // menghapus amalan yang sejak itu sudah dipakai mencatat mutaba'ah harian.
    }
};
