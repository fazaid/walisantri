<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->string('hostname')->unique();
            $table->enum('type', ['subdomain', 'custom'])->default('subdomain');
            $table->boolean('is_primary')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->enum('ssl_status', ['pending', 'active', 'failed'])->default('pending');
            $table->timestamps();

            $table->index(['pesantren_id', 'type'], 'idx_tenant_domains_ps_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_domains');
    }
};
