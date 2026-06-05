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
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pesantren');
            $table->string('nama_kontak');
            $table->string('email');
            $table->string('no_hp', 20);
            $table->string('jumlah_santri')->nullable();
            $table->string('kota')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};
