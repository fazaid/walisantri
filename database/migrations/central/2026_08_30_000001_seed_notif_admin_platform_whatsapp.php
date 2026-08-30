<?php

use App\Services\NotifikasiAdminPlatform;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('whatsapp_settings')->insert([
            'key' => 'notif_admin_platform_enabled',
            // Sengaja 0: alert internal ini kategori baru di luar empat pengecualian
            // sempit §12, dan integrasi WhatsApp masih dimatikan di production sejak
            // v4.23. Super Admin yang menyalakannya sendiri setelah menguji token.
            'value' => 0,
            'keterangan' => 'Kirim alert WhatsApp ke nomor admin platform saat lead demo / pesanan upgrade masuk (kill-switch, default MATI)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('whatsapp_message_templates')->insert([
            [
                'key' => 'notif_admin_demo_baru',
                'template' => NotifikasiAdminPlatform::DEFAULT_DEMO_BARU,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'notif_admin_order_baru',
                'template' => NotifikasiAdminPlatform::DEFAULT_ORDER_BARU,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'notif_admin_order_bukti',
                'template' => NotifikasiAdminPlatform::DEFAULT_ORDER_BUKTI,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('platform_contact_settings')->insert([
            'key' => 'admin_whatsapp',
            // Dibiarkan null: diisi Super Admin di halaman Pengaturan WhatsApp.
            // Barisnya tetap dibuat supaya key-nya terdokumentasi di database.
            'value' => null,
            'keterangan' => 'Nomor WhatsApp pemilik platform untuk alert internal (lead demo & pesanan upgrade)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('whatsapp_settings')->where('key', 'notif_admin_platform_enabled')->delete();
        DB::table('whatsapp_message_templates')->whereIn('key', [
            'notif_admin_demo_baru',
            'notif_admin_order_baru',
            'notif_admin_order_bukti',
        ])->delete();
        DB::table('platform_contact_settings')->where('key', 'admin_whatsapp')->delete();
    }
};
