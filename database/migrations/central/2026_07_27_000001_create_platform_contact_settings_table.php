<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_contact_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        DB::table('platform_contact_settings')->insert([
            [
                'key' => 'cs_whatsapp',
                // Disimpan sudah ternormalisasi (081399096658 -> 6281399096658)
                // supaya bisa langsung dipakai sebagai https://wa.me/{value}.
                'value' => '6281399096658',
                'keterangan' => 'Nomor WhatsApp CS untuk tombol bantuan di halaman invoice',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_contact_settings');
    }
};
