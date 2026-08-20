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
//
// Seluruh nilainya `true` — sama dengan default DDL. Pesantren yang sudah berjalan
// tidak boleh kehilangan satu menu pun karena migrasi ini.
return new class extends Migration
{
    public function up(): void
    {
        $sekarang = now();

        $tanpaPengaturan = DB::table('pesantrens')
            ->whereNotIn('id', DB::table('modul_pengaturan')->select('pesantren_id'))
            ->pluck('id');

        if ($tanpaPengaturan->isEmpty()) {
            return;
        }

        DB::table('modul_pengaturan')->insert(
            $tanpaPengaturan->map(fn (int $pesantrenId): array => [
                'pesantren_id' => $pesantrenId,
                'akademik_aktif' => true,
                'tahfidz_aktif' => true,
                'presensi_aktif' => true,
                'kesantrian_aktif' => true,
                'keuangan_aktif' => true,
                'rapor_aktif' => true,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ])->all()
        );
    }

    public function down(): void
    {
        // Sengaja tidak menghapus apa pun: barisnya juga dibuat ProvisionTenant dan
        // ModulPengaturan::untuk(), jadi tidak ada cara membedakan mana yang lahir
        // dari migrasi ini. Menghapus semuanya akan membuang pengaturan yang sudah
        // disunting admin — dan gejalanya adalah menu yang tiba-tiba muncul kembali.
    }
};
