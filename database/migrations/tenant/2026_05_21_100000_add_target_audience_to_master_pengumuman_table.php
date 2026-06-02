<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_pengumuman', function (Blueprint $table) {
            $table->enum('target_audience', ['admin', 'wali', 'semua'])
                ->default('semua')
                ->after('isi_maklumat');
        });
    }

    public function down(): void
    {
        Schema::table('master_pengumuman', function (Blueprint $table) {
            $table->dropColumn('target_audience');
        });
    }
};
