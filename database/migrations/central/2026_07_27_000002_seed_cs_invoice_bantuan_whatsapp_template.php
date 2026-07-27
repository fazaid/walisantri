<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('whatsapp_message_templates')->insert([
            'key' => 'cs_invoice_bantuan',
            'template' => <<<'TEXT'
            Halo, saya butuh bantuan untuk invoice {nomor_invoice} (order {nomor_order}) dari {nama_pesantren}.
            TEXT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('whatsapp_message_templates')->where('key', 'cs_invoice_bantuan')->delete();
    }
};
