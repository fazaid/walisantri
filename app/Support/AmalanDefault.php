<?php

namespace App\Support;

use App\Models\KesantrianAmalMaster;

/**
 * Daftar amalan bawaan untuk pesantren baru.
 *
 * Daftar ini semula hidup di dalam migrasi `2026_06_23_000007`, yang mengisikannya
 * ke setiap pesantren yang ADA SAAT MIGRASI JALAN. Karena migrasi hanya jalan
 * sekali, pesantren yang mendaftar setelah itu tidak pernah kebagian — dan modul
 * Mutaba'ah mereka lumpuh diam-diam: grid Isi Harian tanpa kolom, form tanpa
 * checkbox, dan skor selalu 0% (`MutabaahScoreCalculator::maxScore()` = 0).
 *
 * Karena itu daftarnya dipindah ke sini: milik aplikasi, bukan milik satu migrasi.
 * `OnboardPesantren` memanggilnya saat registrasi, dan migrasi perbaikan
 * `2026_08_13_000003` memakainya untuk menambal pesantren yang telanjur kosong.
 */
class AmalanDefault
{
    /**
     * Bobot menentukan porsi tiap amalan dalam skor harian (§4.2). "Berjamaah"
     * sengaja jauh lebih berat (25 vs 7) karena ia satu-satunya bertipe hitungan
     * — 5 waktu sehari, bukan sekadar ya/tidak.
     *
     * @return list<array<string, mixed>>
     */
    public static function daftar(): array
    {
        return [
            ['kode' => 'jamaah_5_waktu', 'label' => 'Berjamaah', 'tipe' => 'hitungan', 'nilai_maks' => 5, 'satuan' => 'waktu', 'icon' => '🕌', 'bobot' => 25, 'urutan' => 1],
            ['kode' => 'is_rawatib', 'label' => 'Rawatib', 'tipe' => 'boolean', 'nilai_maks' => null, 'satuan' => 'hari', 'icon' => '🌙', 'bobot' => 7, 'urutan' => 2],
            ['kode' => 'is_shalat_malam', 'label' => 'Shalat Malam', 'tipe' => 'boolean', 'nilai_maks' => null, 'satuan' => 'hari', 'icon' => '🌃', 'bobot' => 7, 'urutan' => 3],
            ['kode' => 'is_dhuha', 'label' => 'Dhuha', 'tipe' => 'boolean', 'nilai_maks' => null, 'satuan' => 'hari', 'icon' => '🌅', 'bobot' => 7, 'urutan' => 4],
            ['kode' => 'is_tilawah_1juz', 'label' => 'Tilawah 1 Juz', 'tipe' => 'boolean', 'nilai_maks' => null, 'satuan' => 'hari', 'icon' => '📖', 'bobot' => 7, 'urutan' => 5],
            ['kode' => 'is_infak', 'label' => 'Infak', 'tipe' => 'boolean', 'nilai_maks' => null, 'satuan' => 'hari', 'icon' => '💰', 'bobot' => 7, 'urutan' => 6],
            ['kode' => 'is_puasa', 'label' => 'Puasa Sunnah', 'tipe' => 'boolean', 'nilai_maks' => null, 'satuan' => 'hari', 'icon' => '🤲', 'bobot' => 7, 'urutan' => 7],
        ];
    }

    /**
     * Isikan amalan bawaan untuk satu pesantren.
     *
     * Idempoten lewat `firstOrCreate` pada unique `(pesantren_id, kode)`: aman
     * dipanggil berulang, dan TIDAK menimpa amalan yang sudah diubah admin —
     * label, bobot, atau status aktif yang sudah dikustomisasi tetap utuh.
     */
    public static function untukPesantren(int $pesantrenId): void
    {
        foreach (self::daftar() as $amalan) {
            KesantrianAmalMaster::withoutGlobalScope('pesantren')->firstOrCreate(
                ['pesantren_id' => $pesantrenId, 'kode' => $amalan['kode']],
                $amalan + ['aktif' => true],
            );
        }
    }
}
