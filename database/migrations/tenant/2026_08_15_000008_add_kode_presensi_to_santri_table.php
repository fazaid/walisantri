<?php

use App\Support\KodePresensi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Kode kartu QR santri. SENGAJA terpisah dari santri.uuid — uuid adalah token
// bearer Magic Link (VerifyMagicToken menukarnya jadi Auth::login($wali)), jadi
// mencetaknya di kartu yang dipegang anak sama dengan mencetak kredensial (§13.2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            $table->string('kode_presensi', 16)->nullable()->after('uuid');
            $table->timestamp('kode_presensi_diperbarui_at')->nullable()->after('kode_presensi');
        });

        // Backfill lewat DB::table, BUKAN Eloquent: migrasi berjalan tanpa sesi
        // auth, dan global scope `pesantren` milik Multitenantable akan menyaring
        // habis apa pun yang dibaca lewat model. Baris ber-deleted_at juga wajib
        // kebagian kode — kalau tidak, unique index di bawah bisa menabrak saat
        // santri di-restore.
        DB::table('santri')->orderBy('id')->chunkById(200, function ($daftar): void {
            foreach ($daftar as $santri) {
                DB::table('santri')
                    ->where('id', $santri->id)
                    ->update(['kode_presensi' => KodePresensi::buat()]);
            }
        });

        Schema::table('santri', function (Blueprint $table) {
            // Unique GLOBAL, bukan per-tenant: lebih mudah dinalar, dan kode yang
            // bocor tidak akan pernah jadi kode valid di tenant lain. Global scope
            // tetap jadi lapis kedua saat kode ditukar jadi presensi.
            $table->unique('kode_presensi', 'santri_kode_presensi_unik');
        });
    }

    public function down(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            $table->dropUnique('santri_kode_presensi_unik');
            $table->dropColumn(['kode_presensi', 'kode_presensi_diperbarui_at']);
        });
    }
};
