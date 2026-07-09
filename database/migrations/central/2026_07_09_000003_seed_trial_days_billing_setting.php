<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('billing_settings')->insert([
            'key'        => 'trial_days',
            'value'      => 14,
            'keterangan' => 'Lama masa trial paket Rintisan (hari)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('billing_settings')->where('key', 'trial_days')->delete();
    }
};
