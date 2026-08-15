<?php

namespace App\Support;

use App\Models\PresensiJamPelajaran;

/**
 * Jam pelajaran bawaan untuk pesantren baru.
 *
 * Daftarnya hidup di kelas aplikasi, BUKAN di dalam migrasi — dan itu pelajaran
 * mahal yang sudah pernah dibayar repo ini. Daftar amalan Mutaba'ah dulu ditulis
 * di dalam migrasi `2026_06_23_000007`, yang hanya jalan SEKALI: pesantren yang
 * mendaftar sesudahnya tidak pernah kebagian, dan modulnya lumpuh diam-diam
 * berbulan-bulan (§22, kelas bug v4.21). Pola yang benar sudah ada di
 * `AmalanDefault`; kelas ini mengikutinya.
 */
class PresensiDefault
{
    /**
     * Delapan jam pelajaran, pola madrasah pagi.
     *
     * Jeda antar-jam bukan kelalaian: 09.15–09.35 istirahat, 11.50–13.00 dzuhur
     * dan makan siang. Jeda sengaja TIDAK disimpan sebagai baris berlabel
     * "Istirahat" — tabel ini mengisi daftar pilihan "jam ke berapa" saat
     * mengabsen, dan baris yang tidak pernah bisa dipilih hanya jadi gangguan.
     * Admin yang memang ingin menampilkannya tetap bisa menambah baris berlabel.
     *
     * @return list<array<string, mixed>>
     */
    public static function jamPelajaran(): array
    {
        return [
            ['jam_ke' => 1, 'jam_mulai' => '07:00:00', 'jam_selesai' => '07:45:00', 'label' => null],
            ['jam_ke' => 2, 'jam_mulai' => '07:45:00', 'jam_selesai' => '08:30:00', 'label' => null],
            ['jam_ke' => 3, 'jam_mulai' => '08:30:00', 'jam_selesai' => '09:15:00', 'label' => null],
            ['jam_ke' => 4, 'jam_mulai' => '09:35:00', 'jam_selesai' => '10:20:00', 'label' => null],
            ['jam_ke' => 5, 'jam_mulai' => '10:20:00', 'jam_selesai' => '11:05:00', 'label' => null],
            ['jam_ke' => 6, 'jam_mulai' => '11:05:00', 'jam_selesai' => '11:50:00', 'label' => null],
            ['jam_ke' => 7, 'jam_mulai' => '13:00:00', 'jam_selesai' => '13:45:00', 'label' => null],
            ['jam_ke' => 8, 'jam_mulai' => '13:45:00', 'jam_selesai' => '14:30:00', 'label' => null],
        ];
    }

    /**
     * Isikan jam bawaan untuk satu pesantren.
     *
     * Idempoten lewat `firstOrCreate` pada unique `(pesantren_id, jam_ke)`: aman
     * dipanggil berulang, dan TIDAK menimpa jam yang sudah diubah admin — waktu
     * mulai/selesai, label, maupun status aktif yang sudah dikustomisasi tetap utuh.
     */
    public static function untukPesantren(int $pesantrenId): void
    {
        foreach (self::jamPelajaran() as $jam) {
            PresensiJamPelajaran::withoutGlobalScope('pesantren')->firstOrCreate(
                ['pesantren_id' => $pesantrenId, 'jam_ke' => $jam['jam_ke']],
                $jam + ['aktif' => true],
            );
        }
    }
}
