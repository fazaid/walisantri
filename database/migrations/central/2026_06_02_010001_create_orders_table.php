<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')->constrained('pesantrens')->cascadeOnDelete();
            $table->foreignId('kupon_id')->nullable()->constrained('kupons')->nullOnDelete();
            $table->string('nomor_order')->unique();
            $table->enum('paket_target', ['gratis', 'rintisan', 'berkembang', 'maju']);
            $table->unsignedInteger('durasi_bulan');
            $table->unsignedInteger('max_santri_kuota_target');
            $table->unsignedBigInteger('harga_per_bulan');
            $table->unsignedBigInteger('harga_total_sebelum_diskon');
            $table->unsignedBigInteger('diskon_nominal')->default(0);
            $table->unsignedBigInteger('harga_total');
            $table->unsignedInteger('bonus_bulan')->default(0);
            $table->unsignedInteger('durasi_total_bulan');
            $table->string('kode_kupon_snapshot')->nullable();
            $table->enum('status', [
                'pending_payment',
                'awaiting_confirmation',
                'confirmed',
                'rejected',
                'expired',
            ])->default('pending_payment');
            $table->text('catatan_admin')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expired_at_baru')->nullable();
            $table->timestamps();

            $table->index('pesantren_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
