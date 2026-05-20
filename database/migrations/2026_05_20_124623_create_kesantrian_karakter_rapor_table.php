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
        Schema::create('kesantrian_karakter_rapor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('santri_id')
                ->constrained('santri')
                ->cascadeOnDelete();
            $table->enum('periode', ['Bulanan', 'Semester']);
            $table->date('tanggal_input');

            // Adab (7 kolom)
            $table->enum('adab_ustadz',  ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('adab_tamu',    ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('adab_asrama',  ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('adab_kelas',   ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('adab_sholat',  ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('adab_quran',   ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('adab_minum',   ['A', 'B', 'C', 'D'])->default('B');

            // Kepribadian (9 kolom)
            $table->enum('kepribadian_tanggungjawab', ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('kepribadian_kemandirian',   ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('kepribadian_kepatuhan',     ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('kepribadian_kebersihan',    ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('kepribadian_mengelola',     ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('kepribadian_kepedulian',    ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('kepribadian_empati',        ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('kepribadian_kebersamaan',   ['A', 'B', 'C', 'D'])->default('B');
            $table->enum('kepribadian_kedisiplinan',  ['A', 'B', 'C', 'D'])->default('B');

            $table->text('log_kasus_khusus')->nullable();
            $table->timestamps();

            $table->index(['pesantren_id', 'santri_id', 'tanggal_input'], 'idx_karakter_ps_tgl');
                    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kesantrian_karakter_rapor');
    }
};
