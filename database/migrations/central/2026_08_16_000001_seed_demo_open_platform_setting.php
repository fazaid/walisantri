<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // updateOrInsert, bukan insert: PlatformSetting::set() memakai
        // updateOrCreate, jadi baris ini bisa sudah lahir lebih dulu kalau
        // toggle-nya sempat dipakai sebelum migrasi ini jalan.
        DB::table('platform_settings')->updateOrInsert(
            ['key' => 'demo_open'],
            [
                // Default terbuka — perilaku hari ini, supaya migrasi ini tidak
                // diam-diam menutup satu-satunya pintu masuk lead yang tersisa
                // saat pendaftaran mandiri kebetulan sedang ditutup.
                'value' => config('app.demo_open', true) ? 1 : 0,
                'keterangan' => 'Buka/tutup halaman ajukan demo /demo (kill-switch)',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('platform_settings')->where('key', 'demo_open')->delete();
    }
};
