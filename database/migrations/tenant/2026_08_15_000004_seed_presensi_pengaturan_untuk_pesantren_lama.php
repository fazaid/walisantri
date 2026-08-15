<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Tenant yang sudah ada tidak pernah melewati ProvisionTenant lagi, jadi barisnya
// diisikan di sini. Idempoten: hanya pesantren yang belum punya baris yang disisipkan,
// sehingga aman dijalankan ulang.
//
// DB::table(), bukan Eloquent: saat migrasi berjalan tidak ada sesi auth, dan global
// scope `pesantren` milik Multitenantable akan menyaring habis apa pun yang dibaca
// lewat model.
return new class extends Migration
{
    public function up(): void
    {
        $sekarang = now();

        $tanpaPengaturan = DB::table('pesantrens')
            ->whereNotIn('id', DB::table('presensi_pengaturan')->select('pesantren_id'))
            ->pluck('id');

        if ($tanpaPengaturan->isEmpty()) {
            return;
        }

        DB::table('presensi_pengaturan')->insert(
            $tanpaPengaturan->map(fn (int $pesantrenId): array => [
                'pesantren_id' => $pesantrenId,
                'presensi_per_jam_aktif' => false,
                'jam_masuk' => '07:00:00',
                'toleransi_terlambat_menit' => 15,
                'hari_libur_mingguan' => '[0]',
                'batas_edit_ustadz_hari' => 7,
                'izin_wali_aktif' => true,
                'qr_aktif' => true,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ])->all()
        );
    }

    public function down(): void
    {
        // Sengaja tidak menghapus apa pun: barisnya juga dibuat ProvisionTenant dan
        // PresensiPengaturan::untuk(), jadi tidak ada cara membedakan mana yang lahir
        // dari migrasi ini. Menghapus semuanya akan membuang pengaturan yang sudah
        // disunting admin.
    }
};
