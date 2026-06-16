<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $kuotaRintisan = (int) (DB::table('billing_settings')
            ->where('key', 'kuota_rintisan')
            ->value('value') ?: 100);

        // Upgrade semua tenant gratis ke rintisan trial 30 hari
        if (DB::getDriverName() === 'sqlite') {
            DB::table('pesantrens')
                ->where('paket_langganan', 'gratis')
                ->update([
                    'paket_langganan'  => 'rintisan',
                    'max_santri_kuota' => $kuotaRintisan,
                    'expired_at'       => DB::raw("datetime(COALESCE(expired_at, 'now'), '+30 days')"),
                    'updated_at'       => now(),
                ]);
        } else {
            DB::table('pesantrens')
                ->where('paket_langganan', 'gratis')
                ->update([
                    'paket_langganan'  => 'rintisan',
                    'max_santri_kuota' => $kuotaRintisan,
                    'expired_at'       => DB::raw("COALESCE(expired_at, NOW()) + INTERVAL '30 days'"),
                    'updated_at'       => now(),
                ]);
        }

        // Hapus kuota_gratis dari billing_settings
        DB::table('billing_settings')->where('key', 'kuota_gratis')->delete();
    }

    public function down(): void
    {
        DB::table('billing_settings')->insertOrIgnore([
            'key'        => 'kuota_gratis',
            'value'      => 5,
            'keterangan' => 'Kuota santri paket Gratis',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
