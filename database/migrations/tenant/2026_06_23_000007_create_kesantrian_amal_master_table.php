<?php

use App\Models\Pesantren;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kesantrian_amal_master', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->string('kode', 50);
            $table->string('label', 100);
            $table->enum('tipe', ['boolean', 'hitungan'])->default('boolean');
            $table->unsignedSmallInteger('nilai_maks')->nullable();
            $table->string('satuan', 20)->default('hari');
            $table->string('icon', 10)->nullable();
            $table->unsignedSmallInteger('bobot')->default(7);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['pesantren_id', 'kode']);
        });

        $default = [
            ['kode' => 'jamaah_5_waktu',  'label' => 'Berjamaah',    'tipe' => 'hitungan', 'nilai_maks' => 5,    'satuan' => 'waktu', 'icon' => '🕌', 'bobot' => 25, 'urutan' => 1],
            ['kode' => 'is_rawatib',      'label' => 'Rawatib',       'tipe' => 'boolean',  'nilai_maks' => null, 'satuan' => 'hari',  'icon' => '🌙', 'bobot' => 7,  'urutan' => 2],
            ['kode' => 'is_shalat_malam', 'label' => 'Shalat Malam',  'tipe' => 'boolean',  'nilai_maks' => null, 'satuan' => 'hari',  'icon' => '🌃', 'bobot' => 7,  'urutan' => 3],
            ['kode' => 'is_dhuha',        'label' => 'Dhuha',         'tipe' => 'boolean',  'nilai_maks' => null, 'satuan' => 'hari',  'icon' => '🌅', 'bobot' => 7,  'urutan' => 4],
            ['kode' => 'is_tilawah_1juz', 'label' => 'Tilawah 1 Juz', 'tipe' => 'boolean',  'nilai_maks' => null, 'satuan' => 'hari',  'icon' => '📖', 'bobot' => 7,  'urutan' => 5],
            ['kode' => 'is_infak',        'label' => 'Infak',         'tipe' => 'boolean',  'nilai_maks' => null, 'satuan' => 'hari',  'icon' => '💰', 'bobot' => 7,  'urutan' => 6],
            ['kode' => 'is_puasa',        'label' => 'Puasa Sunnah',  'tipe' => 'boolean',  'nilai_maks' => null, 'satuan' => 'hari',  'icon' => '🤲', 'bobot' => 7,  'urutan' => 7],
        ];

        $now = now();

        foreach (Pesantren::pluck('id') as $pesantrenId) {
            $rows = array_map(function (array $item) use ($pesantrenId, $now) {
                return $item + [
                    'pesantren_id' => $pesantrenId,
                    'aktif'        => true,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }, $default);

            DB::table('kesantrian_amal_master')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kesantrian_amal_master');
    }
};
