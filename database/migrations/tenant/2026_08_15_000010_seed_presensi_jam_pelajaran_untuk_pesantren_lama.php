<?php

use App\Support\PresensiDefault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Tenant yang sudah ada tidak pernah melewati ProvisionTenant lagi, jadi jam
// bawaannya diisikan di sini — lapis kedua dari tiga (yang ketiga:
// PresensiJamPelajaran::untukPesantren() saat halaman per jam dibuka).
//
// Daftarnya diambil dari App\Support\PresensiDefault, BUKAN disalin ke sini.
// Menyalinnya adalah persis kesalahan yang membuat modul Mutaba'ah lumpuh
// berbulan-bulan: daftar amalan hidup di dalam migrasi yang cuma jalan sekali,
// jadi pesantren yang mendaftar sesudahnya tidak pernah kebagian (§22, kelas bug v4.21).
return new class extends Migration
{
    public function up(): void
    {
        $sekarang = now();

        $pesantrenIds = DB::table('pesantrens')->pluck('id');

        foreach ($pesantrenIds as $pesantrenId) {
            $sudahAda = DB::table('presensi_jam_pelajaran')
                ->where('pesantren_id', $pesantrenId)
                ->pluck('jam_ke')
                ->all();

            $baris = collect(PresensiDefault::jamPelajaran())
                ->reject(fn (array $jam): bool => in_array($jam['jam_ke'], $sudahAda, true))
                ->map(fn (array $jam): array => $jam + [
                    'pesantren_id' => $pesantrenId,
                    'aktif' => true,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ])
                ->all();

            if ($baris !== []) {
                DB::table('presensi_jam_pelajaran')->insert($baris);
            }
        }
    }

    public function down(): void
    {
        // Sengaja tidak menghapus apa pun: jam yang sama juga dibuat ProvisionTenant,
        // dan jam bawaan yang sudah disunting admin (diubah waktunya, dinonaktifkan)
        // tidak bisa dibedakan dari yang lahir di sini.
    }
};
