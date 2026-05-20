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
        Schema::create('pesantrens', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pesantren');
            $table->string('slug')->unique();           // URL routing tenant
            $table->enum('paket_langganan', [
                'rintisan',
                'berkembang',
                'akselerasi',
                'besar',
            ])->default('rintisan');
            $table->unsignedInteger('max_santri_kuota')->default(100);
            $table->enum('status_berlangganan', [
                'trial',
                'active',
                'suspended',
                'expired',
            ])->default('trial');
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            // Index untuk query lifecycle & billing oleh super_admin
            $table->index(['status_berlangganan', 'expired_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesantrens');
    }
};
