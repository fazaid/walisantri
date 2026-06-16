<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->unsignedBigInteger('value');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        DB::table('billing_settings')->insert([
            ['key' => 'harga_rintisan',          'value' => 150000,  'keterangan' => 'Harga paket Rintisan per bulan (Rp)',              'created_at' => now(), 'updated_at' => now()],
            ['key' => 'harga_berkembang',         'value' => 350000,  'keterangan' => 'Harga paket Berkembang per bulan (Rp)',             'created_at' => now(), 'updated_at' => now()],
            ['key' => 'harga_maju_base',          'value' => 750000,  'keterangan' => 'Harga dasar paket Maju per bulan (Rp)',             'created_at' => now(), 'updated_at' => now()],
            ['key' => 'harga_maju_per_100_santri','value' => 100000,  'keterangan' => 'Biaya tambahan per 100 santri di atas 1.000',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'kuota_gratis',             'value' => 5,       'keterangan' => 'Kuota santri paket Gratis',                        'created_at' => now(), 'updated_at' => now()],
            ['key' => 'kuota_rintisan',           'value' => 100,     'keterangan' => 'Kuota santri paket Rintisan',                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'kuota_berkembang',         'value' => 500,     'keterangan' => 'Kuota santri paket Berkembang',                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'kuota_maju_base',          'value' => 1000,    'keterangan' => 'Kuota santri dasar paket Maju',                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'bonus_bulan_enam',         'value' => 1,       'keterangan' => 'Bulan gratis saat berlangganan 6 bulan',           'created_at' => now(), 'updated_at' => now()],
            ['key' => 'bonus_bulan_tahunan',      'value' => 2,       'keterangan' => 'Bulan gratis saat berlangganan 12 bulan',          'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_settings');
    }
};
