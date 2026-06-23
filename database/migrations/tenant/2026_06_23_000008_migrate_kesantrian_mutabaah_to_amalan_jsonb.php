<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kesantrian_mutabaah', function ($table) {
            $table->jsonb('amalan')->nullable()->after('tanggal');
        });

        DB::table('kesantrian_mutabaah')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('kesantrian_mutabaah')
                    ->where('id', $row->id)
                    ->update([
                        'amalan' => json_encode([
                            'jamaah_5_waktu'  => $row->jamaah_5_waktu,
                            'is_rawatib'      => (bool) $row->is_rawatib,
                            'is_shalat_malam' => (bool) $row->is_shalat_malam,
                            'is_dhuha'        => (bool) $row->is_dhuha,
                            'is_tilawah_1juz' => (bool) $row->is_tilawah_1juz,
                            'is_infak'        => (bool) $row->is_infak,
                            'is_puasa'        => (bool) $row->is_puasa,
                        ]),
                    ]);
            }
        });

        Schema::table('kesantrian_mutabaah', function ($table) {
            $table->jsonb('amalan')->nullable(false)->default('{}')->change();
            $table->dropColumn([
                'jamaah_5_waktu',
                'is_rawatib',
                'is_shalat_malam',
                'is_dhuha',
                'is_tilawah_1juz',
                'is_infak',
                'is_puasa',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('kesantrian_mutabaah', function ($table) {
            $table->unsignedTinyInteger('jamaah_5_waktu')->default(5);
            $table->boolean('is_rawatib')->default(false);
            $table->boolean('is_shalat_malam')->default(false);
            $table->boolean('is_dhuha')->default(false);
            $table->boolean('is_tilawah_1juz')->default(false);
            $table->boolean('is_infak')->default(false);
            $table->boolean('is_puasa')->default(false);
        });

        DB::table('kesantrian_mutabaah')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $amalan = json_decode($row->amalan ?? '{}', true) ?? [];

                DB::table('kesantrian_mutabaah')
                    ->where('id', $row->id)
                    ->update([
                        'jamaah_5_waktu'  => $amalan['jamaah_5_waktu'] ?? 5,
                        'is_rawatib'      => $amalan['is_rawatib'] ?? false,
                        'is_shalat_malam' => $amalan['is_shalat_malam'] ?? false,
                        'is_dhuha'        => $amalan['is_dhuha'] ?? false,
                        'is_tilawah_1juz' => $amalan['is_tilawah_1juz'] ?? false,
                        'is_infak'        => $amalan['is_infak'] ?? false,
                        'is_puasa'        => $amalan['is_puasa'] ?? false,
                    ]);
            }
        });

        Schema::table('kesantrian_mutabaah', function ($table) {
            $table->dropColumn('amalan');
        });
    }
};
