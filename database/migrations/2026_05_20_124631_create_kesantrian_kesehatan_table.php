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
        Schema::create('kesantrian_kesehatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('santri_id')
                ->constrained('santri')
                ->cascadeOnDelete();
            $table->date('tanggal_periksa');
            $table->float('berat_badan')->nullable();    // kg
            $table->float('tinggi_badan')->nullable();   // cm
            $table->enum('kategori_keluhan', [
                'Demam',
                'Batuk_Pilek',
                'Sakit_Perut',
                'Pusing',
                'Kulit_Gatal',
                'Luka_Fisik',
                'Lainnya',
            ]);
            $table->text('detail_keluhan_teks')->nullable();
            $table->text('tindakan_dan_obat');
            $table->enum('status_pemulihan', [
                'Rawat_Mandiri',
                'Istirahat_Total',
                'Rujukan_Luar',
            ]);
            $table->timestamps();

            $table->index(['pesantren_id', 'santri_id', 'tanggal_periksa'], 'idx_kesehatan_ps_tgl');
            // Index untuk trigger Observer kesehatan → mutabaah
            $table->index(['santri_id', 'tanggal_periksa', 'status_pemulihan'], 'idx_kesehatan_observer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kesantrian_kesehatan');
    }
};
