<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kesantrian_kesehatan', function (Blueprint $table) {
            $table->string('jenis_rekam', 10)->default('keluhan')->after('santri_id');

            // Rekam rutin tidak memerlukan keluhan — jadikan nullable
            $table->enum('kategori_keluhan', [
                'Demam', 'Batuk_Pilek', 'Sakit_Perut', 'Pusing', 'Kulit_Gatal', 'Luka_Fisik', 'Lainnya',
            ])->nullable()->change();

            $table->text('tindakan_dan_obat')->nullable()->change();

            $table->enum('status_pemulihan', [
                'Rawat_Mandiri', 'Istirahat_Total', 'Rujukan_Luar',
            ])->nullable()->change();
        });
    }

    public function down(): void
    {
        // Isi default sebelum NOT NULL dikembalikan
        DB::table('kesantrian_kesehatan')
            ->whereNull('kategori_keluhan')
            ->update(['kategori_keluhan' => 'Lainnya']);
        DB::table('kesantrian_kesehatan')
            ->whereNull('tindakan_dan_obat')
            ->update(['tindakan_dan_obat' => '-']);
        DB::table('kesantrian_kesehatan')
            ->whereNull('status_pemulihan')
            ->update(['status_pemulihan' => 'Rawat_Mandiri']);

        Schema::table('kesantrian_kesehatan', function (Blueprint $table) {
            $table->dropColumn('jenis_rekam');

            $table->enum('kategori_keluhan', [
                'Demam', 'Batuk_Pilek', 'Sakit_Perut', 'Pusing', 'Kulit_Gatal', 'Luka_Fisik', 'Lainnya',
            ])->nullable(false)->change();

            $table->text('tindakan_dan_obat')->nullable(false)->change();

            $table->enum('status_pemulihan', [
                'Rawat_Mandiri', 'Istirahat_Total', 'Rujukan_Luar',
            ])->nullable(false)->change();
        });
    }
};
