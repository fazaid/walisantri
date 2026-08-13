<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kolom `pengajar` (string bebas) sengaja dipertahankan, bukan diganti: pembina
// ekskul sering pelatih luar yang tidak punya akun (silat, pramuka). `pembina_id`
// dipakai bila pembinanya ustadz internal; `pengajar` jadi jalur nama bebas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ekskul_masters', function (Blueprint $table) {
            $table->foreignId('pembina_id')
                ->nullable()
                ->after('deskripsi')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('pembina_id');
        });
    }

    public function down(): void
    {
        Schema::table('ekskul_masters', function (Blueprint $table) {
            $table->dropIndex(['pembina_id']);
            $table->dropConstrainedForeignId('pembina_id');
        });
    }
};
