<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_spp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('tagihan_spp_id')
                ->constrained('tagihan_spp')
                ->cascadeOnDelete();
            $table->unsignedInteger('jumlah');
            $table->date('tanggal_bayar');
            $table->string('metode_bayar')->default('tunai'); // tunai | transfer_bank | lainnya
            $table->text('catatan')->nullable();
            // FK logis ke users (central DB) — tidak di-enforce via FK fisik
            $table->unsignedBigInteger('dicatat_oleh')->nullable();
            $table->timestamps();

            $table->index(['pesantren_id', 'tagihan_spp_id'], 'pembayaran_spp_tagihan_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_spp');
    }
};
