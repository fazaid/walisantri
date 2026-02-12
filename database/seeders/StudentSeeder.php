<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\ParentProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat Data Orang Tua Contoh
        $ortu = ParentProfile::create([
            'nama_ayah' => 'Sulaeman',
            'nama_ibu' => 'Siti Aminah',
            'no_hp_wali' => '081234567890',
            'alamat_ortu' => 'Jl. Palestina No. 1, Jakarta',
        ]);

        // 2. Buat Data Santri yang Terhubung ke Ortu Tersebut
        Student::create([
            'parent_profile_id' => $ortu->id, // Menghubungkan ke ID Ortu di atas
            'nisn' => '1234567890',
            'nis' => '2024001',
            'nik' => '31710123456789',
            'nama_lengkap' => 'Ahmad Fauzan',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '2010-05-20',
            'alamat' => 'Jl. Palestina No. 1, Jakarta',
            'tanggal_masuk' => '2024-01-01',
            'status_aktif' => 'Aktif',
            'kelas_saat_ini' => '7A MTS',
            'diterima_di_kelas' => '7A MTS',
            'wali_kelas' => 'Ustadz Abdullah',
        ]);

        // Tambahkan satu lagi contoh santri (anak kedua dari ortu yang sama)
        Student::create([
            'parent_profile_id' => $ortu->id,
            'nisn' => '1234567891',
            'nis' => '2024002',
            'nik' => '31710123456790',
            'nama_lengkap' => 'Zahra Aulia',
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '2012-08-15',
            'alamat' => 'Jl. Palestina No. 1, Jakarta',
            'tanggal_masuk' => '2024-01-01',
            'status_aktif' => 'Aktif',
            'kelas_saat_ini' => '5A MI',
            'diterima_di_kelas' => '5A MI',
            'wali_kelas' => 'Ustadzah Fatimah',
        ]);
    }
}
