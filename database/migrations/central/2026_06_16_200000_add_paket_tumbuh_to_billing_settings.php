<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('billing_settings')->upsert([
            [
                'key'         => 'harga_tumbuh',
                'value'       => 299000,
                'keterangan'  => 'Harga paket Tumbuh per bulan',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'kuota_tumbuh',
                'value'       => 250,
                'keterangan'  => 'Kuota santri paket Tumbuh',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ], uniqueBy: ['key'], update: ['value', 'keterangan', 'updated_at']);
    }

    public function down(): void
    {
        DB::table('billing_settings')
            ->whereIn('key', ['harga_tumbuh', 'kuota_tumbuh'])
            ->delete();
    }
};
