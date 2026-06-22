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
        Schema::table('santri', function (Blueprint $table) {
            $table->string('nama_panggilan', 100)->nullable()->after('nama_lengkap');
            $table->string('nama_ayah')->nullable()->after('nama_panggilan');
            $table->string('nama_ibu')->nullable()->after('nama_ayah');
            $table->text('alamat_lengkap')->nullable()->after('nama_ibu');
            $table->unsignedSmallInteger('jumlah_saudara')->nullable()->after('alamat_lengkap');
            $table->text('ciri_fisik')->nullable()->after('jumlah_saudara');
            $table->string('cita_cita')->nullable()->after('ciri_fisik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            $table->dropColumn([
                'nama_panggilan',
                'nama_ayah',
                'nama_ibu',
                'alamat_lengkap',
                'jumlah_saudara',
                'ciri_fisik',
                'cita_cita',
            ]);
        });
    }
};
