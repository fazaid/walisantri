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
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->string('pendidikan_ayah')->nullable()->after('pekerjaan_ayah');
            $table->string('pendidikan_ibu')->nullable()->after('pekerjaan_ibu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->dropColumn(['pendidikan_ayah', 'pendidikan_ibu']);
        });
    }
};
