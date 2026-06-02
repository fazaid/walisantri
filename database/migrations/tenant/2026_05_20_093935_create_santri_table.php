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
        Schema::create('santri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('wali_santri_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('pembimbing_ustadz_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->uuid('uuid')->unique();             // Magic Link token
            $table->string('nis', 30);                  // Nomor Induk Santri
            $table->string('nama_lengkap');
            $table->string('kelas', 50);
            $table->string('kamar', 50);
            $table->boolean('status_aktif')->default(true);
            $table->softDeletes();                      // deleted_at — histori tetap ada
            $table->timestamps();

            // Composite index untuk CheckTenantQuota middleware
            $table->index(['pesantren_id', 'status_aktif']);

            // Composite index untuk query filter per kamar/kelas
            $table->index(['pesantren_id', 'kamar']);
            $table->index(['pesantren_id', 'kelas']);

            // NIS unik per pesantren (bukan global)
            $table->unique(['pesantren_id', 'nis']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('santri');
    }
};
