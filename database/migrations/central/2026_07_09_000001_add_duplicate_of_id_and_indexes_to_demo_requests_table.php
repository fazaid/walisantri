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
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->foreignId('duplicate_of_id')->nullable()->after('catatan')
                ->constrained('demo_requests')->nullOnDelete();
            $table->index('email');
            $table->index('no_hp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('duplicate_of_id');
            $table->dropIndex(['email']);
            $table->dropIndex(['no_hp']);
        });
    }
};
