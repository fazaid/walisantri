<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('whatsapp_settings')->insert([
            'key' => 'notif_demo_terima_kasih_enabled',
            'value' => 1,
            'keterangan' => 'Kirim ucapan terima kasih + link grup support via WhatsApp otomatis saat calon pelanggan mengisi form demo (kill-switch)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('whatsapp_message_templates')->insert([
            'key' => 'notif_demo_terima_kasih',
            'template' => <<<'TEXT'
            Assalamu'alaikum, {nama_kontak}.

            Terima kasih sudah mendaftar demo Walisantri.com untuk {nama_pesantren}. 🙏

            Tim kami akan segera menghubungi Anda. Sambil menunggu, silakan gabung grup WhatsApp support kami untuk tanya-jawab & bantuan:
            https://chat.whatsapp.com/XXXXXXXXXXXXXXX

            Terima kasih.
            TEXT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('whatsapp_settings')->where('key', 'notif_demo_terima_kasih_enabled')->delete();
        DB::table('whatsapp_message_templates')->where('key', 'notif_demo_terima_kasih')->delete();
    }
};
