<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('whatsapp_settings')->insert([
            'key' => 'notif_order_dikonfirmasi_enabled',
            'value' => 1,
            'keterangan' => 'Kirim notifikasi WhatsApp otomatis ke admin pesantren saat Super Admin mengonfirmasi order upgrade/perpanjangan (kill-switch)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('whatsapp_message_templates')->insert([
            'key' => 'notif_order_dikonfirmasi',
            'template' => <<<'TEXT'
            Assalamu'alaikum, Admin {nama_pesantren}.

            Pembayaran Anda telah dikonfirmasi Super Admin.

            Nomor order   : {nomor_order}
            Paket aktif   : {paket}
            Durasi        : {durasi_bulan} bulan
            Total dibayar : {total_dibayar}
            Aktif hingga  : {tanggal_expired}

            Terima kasih telah berlangganan Walisantri.com.
            {link_billing}
            TEXT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('whatsapp_settings')->where('key', 'notif_order_dikonfirmasi_enabled')->delete();
        DB::table('whatsapp_message_templates')->where('key', 'notif_order_dikonfirmasi')->delete();
    }
};
