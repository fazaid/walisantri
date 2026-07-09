<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            $table->index('pembimbing_ustadz_id');
            $table->index('wali_santri_id');
        });

        Schema::table('tahfidz_rapor', function (Blueprint $table) {
            $table->index('penguji_id');
        });
    }

    public function down(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            $table->dropIndex(['pembimbing_ustadz_id']);
            $table->dropIndex(['wali_santri_id']);
        });

        Schema::table('tahfidz_rapor', function (Blueprint $table) {
            $table->dropIndex(['penguji_id']);
        });
    }
};
