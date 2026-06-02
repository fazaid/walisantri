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
            $table->string('slug')->unique();
            $table->enum('paket_langganan', [
                'gratis',
                'rintisan',
                'berkembang',
                'maju',
            ])->default('gratis');
            $table->integer('max_santri_kuota')->default(10);
            $table->enum('status_berlangganan', [
                'trial',
                'active',
                'suspended',
                'expired',
            ])->default('trial');
            $table->timestamp('expired_at')->nullable();
            $table->integer('santri_count_cache')->default(0);
            $table->jsonb('onboarding_completed_steps')->nullable();
            $table->jsonb('profil')->nullable();
            $table->timestamps();

            $table->index(['status_berlangganan', 'expired_at'], 'idx_pesantrens_status_exp');
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
