<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->unsignedTinyInteger('value');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        DB::table('whatsapp_settings')->insert([
            [
                'key' => 'reminder_expired_enabled',
                'value' => 1,
                'keterangan' => 'Kirim reminder WhatsApp H-3/H-1 sebelum langganan expired (kill-switch)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};
