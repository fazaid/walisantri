<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahfidz_rapor', function (Blueprint $table) {
            $table->foreignId('penguji_id')
                ->nullable()
                ->after('santri_id')
                ->constrained('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tahfidz_rapor', function (Blueprint $table) {
            $table->dropConstrainedForeignId('penguji_id');
        });
    }
};
