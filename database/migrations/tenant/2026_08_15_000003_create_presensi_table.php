<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('santri_id')
                ->constrained('santri')
                ->cascadeOnDelete();

            $table->date('tanggal');

            // 0 = presensi harian, 1..N = jam pelajaran ke-N.
            //
            // ⚠️ NOT NULL, dan itu bukan kebetulan. Rancangan yang lebih "natural"
            // adalah jam_pelajaran_id nullable, tapi kolom diskriminator nullable
            // akan meruntuhkan unique di bawah secara DIAM-DIAM: di dalam UNIQUE,
            // NULL tidak pernah sama dengan NULL, sehingga (santri_id, tanggal, NULL)
            // bisa disisipkan tak terbatas. Berlaku di PostgreSQL MAUPUN SQLite, jadi
            // tidak ada perbedaan engine yang akan membongkarnya di CI. Kelas bug yang
            // sama sudah pernah terjadi di nilai_akademik (lihat 2026_08_15_000001).
            $table->smallInteger('jam_ke')->default(0);

            // Diisi hanya saat jam_ke > 0 (Fase 6). Sengaja TIDAK ada FK ke master jam
            // pelajaran: menghapus "jam ke-8" dari master tidak boleh ikut menghapus
            // riwayat presensi jam ke-8 tahun lalu, jadi jam_ke tetap angka lepas.
            $table->foreignId('mata_pelajaran_id')->nullable()
                ->constrained('mata_pelajaran')
                ->nullOnDelete();

            // SNAPSHOT kelas saat presensi dicatat, bukan turunan santri.kelas_id.
            // Santri bisa pindah kelas di tengah tahun ajaran, dan rekap per kelas
            // harus mencerminkan kelas saat itu — bukan kelasnya hari ini.
            $table->foreignId('kelas_id')->nullable()
                ->constrained('kelas')
                ->nullOnDelete();

            $table->enum('status', [
                'Hadir',
                'Sakit',
                'Izin',
                'Alpa',
                'Terlambat',
                'Pulang',
                'Dispensasi',
            ])->default('Hadir');

            $table->smallInteger('menit_terlambat')->nullable();
            $table->string('catatan', 255)->nullable();
            $table->enum('sumber', ['manual', 'qr', 'izin'])->default('manual');

            // FK LOGIS ke users.id di DB central — tidak di-enforce FK fisik, mengikuti
            // pola uang_saku_santri.dicatat_oleh / pembayaran_spp.dicatat_oleh, supaya
            // migrasi ke schema-per-tenant nanti tidak terhalang.
            $table->unsignedBigInteger('dicatat_oleh')->nullable();
            $table->timestamp('dicatat_at')->nullable();
            $table->timestamps();

            // Kolom presensi_izin_id menyusul di Fase 4 lewat migrasi ALTER tersendiri —
            // ia FK ke presensi_izin yang belum lahir di fase ini.

            $table->unique(['santri_id', 'tanggal', 'jam_ke'], 'presensi_unik_santri_tgl_jam');
            $table->index(['pesantren_id', 'tanggal', 'jam_ke'], 'idx_presensi_ps_tgl_jam');
            $table->index(['pesantren_id', 'santri_id', 'tanggal'], 'idx_presensi_ps_santri_tgl');
            $table->index(['pesantren_id', 'kelas_id', 'tanggal'], 'idx_presensi_ps_kelas_tgl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};
