<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->nullable()
                ->constrained('pesantrens')
                ->nullOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('event');
            $table->string('auditable_type')->nullable();
            $table->bigInteger('auditable_id')->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['pesantren_id', 'event'], 'idx_activity_ps_event');
            $table->index(['user_id', 'created_at'], 'idx_activity_user_date');
            $table->index(['auditable_type', 'auditable_id'], 'idx_activity_auditable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
