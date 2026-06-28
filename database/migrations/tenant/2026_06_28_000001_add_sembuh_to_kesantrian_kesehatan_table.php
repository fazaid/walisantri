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
            $table->date('tanggal_sembuh')->nullable()->after('status_pemulihan');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE kesantrian_kesehatan DROP CONSTRAINT IF EXISTS kesantrian_kesehatan_status_pemulihan_check');
            DB::statement("ALTER TABLE kesantrian_kesehatan ADD CONSTRAINT kesantrian_kesehatan_status_pemulihan_check CHECK (status_pemulihan IN ('Rawat_Mandiri', 'Istirahat_Total', 'Rujukan_Luar', 'Sembuh'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE kesantrian_kesehatan DROP CONSTRAINT IF EXISTS kesantrian_kesehatan_status_pemulihan_check');
            DB::statement("ALTER TABLE kesantrian_kesehatan ADD CONSTRAINT kesantrian_kesehatan_status_pemulihan_check CHECK (status_pemulihan IN ('Rawat_Mandiri', 'Istirahat_Total', 'Rujukan_Luar'))");
        }

        Schema::table('kesantrian_kesehatan', function (Blueprint $table) {
            $table->dropColumn('tanggal_sembuh');
        });
    }
};
