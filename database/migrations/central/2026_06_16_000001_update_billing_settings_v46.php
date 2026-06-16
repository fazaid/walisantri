<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Harga Berkembang turun Rp 450k → Rp 350k (PRD v4.6)
        DB::table('billing_settings')
            ->where('key', 'harga_berkembang')
            ->update(['value' => 350000, 'updated_at' => now()]);

        // Tambah key baru jika belum ada
        DB::table('billing_settings')->upsert([
            ['key' => 'kuota_gratis',    'value' => 5, 'keterangan' => 'Kuota santri paket Gratis',              'created_at' => now(), 'updated_at' => now()],
            ['key' => 'bonus_bulan_enam','value' => 1, 'keterangan' => 'Bulan gratis saat berlangganan 6 bulan', 'created_at' => now(), 'updated_at' => now()],
        ], uniqueBy: ['key'], update: ['value', 'keterangan', 'updated_at']);
    }

    public function down(): void
    {
        DB::table('billing_settings')
            ->where('key', 'harga_berkembang')
            ->update(['value' => 450000, 'updated_at' => now()]);

        DB::table('billing_settings')
            ->whereIn('key', ['kuota_gratis', 'bonus_bulan_enam'])
            ->delete();
    }
};
