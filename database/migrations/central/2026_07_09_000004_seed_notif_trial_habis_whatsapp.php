<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('whatsapp_settings')->insert([
            'key' => 'notif_trial_habis_enabled',
            'value' => 1,
            'keterangan' => 'Kirim notifikasi WhatsApp sekali saat langganan baru saja berubah status ke expired (kill-switch)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('whatsapp_message_templates')->insert([
            'key' => 'notif_trial_habis',
            'template' => <<<'TEXT'
            Assalamu'alaikum, Admin {nama_pesantren}.

            Masa langganan Walisantri.com Anda telah berakhir pada {tanggal_expired}.

            Akses admin/ustadz sudah dikunci dan portal wali santri masuk masa tenggang 7 hari (read-only). Segera perpanjang agar tidak terganggu:
            {link_billing}

            Terima kasih.
            TEXT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('whatsapp_settings')->where('key', 'notif_trial_habis_enabled')->delete();
        DB::table('whatsapp_message_templates')->where('key', 'notif_trial_habis')->delete();
    }
};
