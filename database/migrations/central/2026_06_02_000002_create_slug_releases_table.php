<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slug_releases', function (Blueprint $table) {
            $table->string('slug')->primary();
            $table->timestamp('released_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_releases');
    }
};
