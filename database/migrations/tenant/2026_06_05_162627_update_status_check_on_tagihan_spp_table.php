<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tagihan_spp DROP CONSTRAINT IF EXISTS tagihan_spp_status_check');
        DB::statement("ALTER TABLE tagihan_spp ADD CONSTRAINT tagihan_spp_status_check CHECK (status IN ('belum_bayar','menunggu_konfirmasi','lunas'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tagihan_spp DROP CONSTRAINT IF EXISTS tagihan_spp_status_check');
        DB::statement("ALTER TABLE tagihan_spp ADD CONSTRAINT tagihan_spp_status_check CHECK (status IN ('belum_bayar','lunas'))");
    }
};
