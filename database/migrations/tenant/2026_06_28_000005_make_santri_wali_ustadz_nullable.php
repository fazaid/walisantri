<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            $table->foreignId('wali_santri_id')->nullable()->change();
            $table->foreignId('pembimbing_ustadz_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            $table->foreignId('wali_santri_id')->nullable(false)->change();
            $table->foreignId('pembimbing_ustadz_id')->nullable(false)->change();
        });
    }
};
