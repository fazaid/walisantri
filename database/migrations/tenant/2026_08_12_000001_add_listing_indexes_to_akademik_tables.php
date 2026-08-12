<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nilai_akademik', function (Blueprint $table) {
            // Query default halaman Nilai: where pesantren_id = ? and tahun_ajaran = ?
            // order by created_at desc. Index lama ['pesantren_id','santri_id',
            // 'tahun_ajaran','periode'] tidak terpakai karena santri_id menyela.
            $table->index(
                ['pesantren_id', 'tahun_ajaran', 'created_at'],
                'nilai_akademik_tenant_ta_created_idx'
            );
        });

        Schema::table('santri_ekskuls', function (Blueprint $table) {
            // Tabel hanya punya index('pesantren_id'), sedangkan tabelnya
            // defaultSort('created_at', 'desc').
            $table->index(
                ['pesantren_id', 'created_at'],
                'santri_ekskuls_tenant_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('nilai_akademik', function (Blueprint $table) {
            $table->dropIndex('nilai_akademik_tenant_ta_created_idx');
        });

        Schema::table('santri_ekskuls', function (Blueprint $table) {
            $table->dropIndex('santri_ekskuls_tenant_created_idx');
        });
    }
};
