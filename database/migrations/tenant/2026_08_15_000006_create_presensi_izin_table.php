<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Dua pintu masuk ke tabel yang sama: wali mengajukan lewat portal (status
// 'diajukan', menunggu persetujuan), dan admin/wali kelas membuat langsung
// (status 'disetujui', diajukan_oleh = null).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_izin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();
            $table->foreignId('santri_id')
                ->constrained('santri')
                ->cascadeOnDelete();

            $table->enum('jenis', ['sakit', 'izin', 'pulang', 'dispensasi']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('alasan');

            // ⚠️ Disk 'local', BUKAN 'public'. Surat keterangan dokter adalah data
            // kesehatan anak (PRD §13.2), dan disk public menghasilkan URL yang bisa
            // ditebak tanpa pernah melewati otorisasi. Disajikan lewat rute
            // terotorisasi wali.izin.lampiran, pola orders.bukti-transfer.
            $table->string('lampiran')->nullable();

            $table->enum('status', ['diajukan', 'disetujui', 'ditolak', 'dibatalkan'])
                ->default('diajukan');

            // FK logis ke users.id di DB central — pola uang_saku_santri.dicatat_oleh.
            // NULL pada diajukan_oleh berarti dibuat langsung oleh admin, bukan wali.
            $table->unsignedBigInteger('diajukan_oleh')->nullable();
            $table->unsignedBigInteger('diproses_oleh')->nullable();
            $table->timestamp('diproses_at')->nullable();
            $table->text('catatan_petugas')->nullable();
            $table->timestamps();

            // Sengaja TANPA unique: satu santri boleh punya beberapa pengajuan.
            // Yang menjaga rentang beririsan adalah validasi form, bukan DB —
            // karena "beririsan" bukan kesetaraan yang bisa dinyatakan constraint.
            $table->index(['pesantren_id', 'status'], 'idx_izin_ps_status');
            $table->index(['pesantren_id', 'santri_id', 'tanggal_mulai'], 'idx_izin_ps_santri_tgl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_izin');
    }
};
