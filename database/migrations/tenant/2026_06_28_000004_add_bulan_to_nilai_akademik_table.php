<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nilai_akademik', function (Blueprint $table) {
            $table->string('bulan', 10)->nullable()->after('periode');
            $table->dropUnique(['santri_id', 'mata_pelajaran_id', 'tahun_ajaran', 'periode']);
            $table->unique(['santri_id', 'mata_pelajaran_id', 'tahun_ajaran', 'periode', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::table('nilai_akademik', function (Blueprint $table) {
            $table->dropUnique(['santri_id', 'mata_pelajaran_id', 'tahun_ajaran', 'periode', 'bulan']);
            $table->unique(['santri_id', 'mata_pelajaran_id', 'tahun_ajaran', 'periode']);
            $table->dropColumn('bulan');
        });
    }
};
