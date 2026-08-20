<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Satu baris per pesantren, satu kolom per cluster sidebar yang boleh dimatikan.
// Dibuat ProvisionTenant untuk tenant baru, ditambal migrasi 000002 untuk tenant
// lama, dan tetap menyembuhkan diri lewat ModulPengaturan::untuk() — tiga lapis
// sengaja, sebagai pagar terhadap kelas bug v4.21 (modul Mutaba'ah lumpuh diam-diam
// berbulan-bulan karena satu-satunya pengisi datanya adalah migrasi sekali jalan).
//
// ⚠️ SEMUA DEFAULT `true`. Migrasi ini tidak boleh mengubah apa pun yang dilihat
// pesantren yang sudah berjalan — fitur ini opt-OUT, bukan opt-in. Kolom ini juga
// bukan feature lock paket (§5.1): yang memutuskan admin pesantren, bukan platform.
//
// Cluster Santri sengaja tidak punya kolom di sini: Santri/Kelas/Kamar/Prestasi
// adalah inti sistem, dan tuas untuk mematikannya tidak boleh pernah ada.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modul_pengaturan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')
                ->constrained('pesantrens')
                ->cascadeOnDelete();

            $table->boolean('akademik_aktif')->default(true);
            $table->boolean('tahfidz_aktif')->default(true);
            $table->boolean('presensi_aktif')->default(true);
            $table->boolean('kesantrian_aktif')->default(true);
            $table->boolean('keuangan_aktif')->default(true);
            $table->boolean('rapor_aktif')->default(true);

            $table->timestamps();

            // Nama eksplisit & pendek — PostgreSQL memotong nama index di 63 karakter.
            $table->unique('pesantren_id', 'modul_pengaturan_ps_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modul_pengaturan');
    }
};
