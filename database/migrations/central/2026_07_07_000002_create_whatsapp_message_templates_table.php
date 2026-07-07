<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_message_templates', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('template');
            $table->timestamps();
        });

        DB::table('whatsapp_message_templates')->insert([
            'key' => 'reminder_expired',
            'template' => <<<'TEXT'
            Assalamu'alaikum, Admin {nama_pesantren}.

            Langganan Walisantri.com Anda akan berakhir dalam {sisa_hari} hari (pada {tanggal_expired}).

            Segera perpanjang agar data santri dan akses portal wali tidak terganggu:
            {link_billing}

            Terima kasih.
            TEXT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_templates');
    }
};
