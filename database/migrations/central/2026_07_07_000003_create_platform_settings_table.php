<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->unsignedTinyInteger('value');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        DB::table('platform_settings')->insert([
            [
                'key' => 'registration_open',
                // Pertahankan nilai REGISTRATION_OPEN saat ini sebagai baseline,
                // supaya migrasi ini tidak diam-diam membuka/menutup pendaftaran.
                'value' => config('app.registration_open', true) ? 1 : 0,
                'keterangan' => 'Buka/tutup halaman pendaftaran mandiri /register (kill-switch)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
