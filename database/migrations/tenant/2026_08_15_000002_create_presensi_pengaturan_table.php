<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Satu baris per pesantren. Dibuat ProvisionTenant untuk tenant baru, ditambal
// migrasi 000004 untuk tenant lama, dan tetap menyembuhkan diri lewat
// PresensiPengaturan::untuk() — tiga lapis sengaja, sebagai pagar terhadap kelas
// bug v4.21 (amal master tidak pernah ter-seed untuk tenant baru dan modul
// Mutaba'ah lumpuh diam-diam selama berbulan-bulan).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_pengaturan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();

            $table->boolean('presensi_per_jam_aktif')->default(false);
            $table->time('jam_masuk')->default('07:00:00');

            // Satu angka menit, bukan jam absolut "batas_terlambat": nilai yang sama
            // berlaku untuk jam_masuk (presensi harian) DAN untuk jam_mulai tiap jam
            // pelajaran nanti, sehingga tidak ada dua nilai yang bisa saling menyimpang.
            $table->smallInteger('toleransi_terlambat_menit')->default(15);

            // ⚠️ Penomorannya Carbon::dayOfWeek — 0 = MINGGU, 1 = Senin, … 6 = Sabtu.
            // BUKAN ISO-8601 (yang memakai 1 = Senin … 7 = Minggu). Salah membacanya
            // tidak akan terlihat sampai ada pesantren yang liburnya bukan Minggu,
            // dan saat itu gejalanya adalah "hari efektif" meleset satu hari tanpa
            // ada error apa pun. Default [0] = libur tiap Minggu.
            $table->jsonb('hari_libur_mingguan')->default('[0]');

            // Berapa hari ke belakang ustadz masih boleh mengisi/mengubah presensi.
            // 0 = tanpa batas. Admin pesantren tidak pernah terkena batas ini.
            $table->smallInteger('batas_edit_ustadz_hari')->default(7);

            $table->boolean('izin_wali_aktif')->default(true);
            $table->boolean('qr_aktif')->default(true);
            $table->timestamps();

            $table->unique('pesantren_id', 'presensi_pengaturan_ps_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_pengaturan');
    }
};
