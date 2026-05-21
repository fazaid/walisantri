<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_pengumuman', function (Blueprint $table) {
            // Lepas foreign key constraint dulu, lalu jadikan nullable
            $table->dropForeign(['pesantren_id']);
            $table->unsignedBigInteger('pesantren_id')->nullable()->change();
            $table->foreign('pesantren_id')
                ->references('id')
                ->on('pesantrens')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('master_pengumuman', function (Blueprint $table) {
            $table->dropForeign(['pesantren_id']);
            $table->unsignedBigInteger('pesantren_id')->nullable(false)->change();
            $table->foreign('pesantren_id')
                ->references('id')
                ->on('pesantrens')
                ->cascadeOnDelete();
        });
    }
};
