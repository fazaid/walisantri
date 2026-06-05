<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tagihan_spp', function (Blueprint $table) {
            $table->string('bukti_transfer')->nullable()->after('status');
            $table->timestamp('dikonfirmasi_wali_at')->nullable()->after('bukti_transfer');
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_spp', function (Blueprint $table) {
            $table->dropColumn(['bukti_transfer', 'dikonfirmasi_wali_at']);
        });
    }
};
