<?php

// File: database/seeders/TenantDummySeeder.php

namespace Database\Seeders;

use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantDummySeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. Tenant ---
        $pesantren = Pesantren::updateOrCreate(
            ['slug' => 'pesantren-demo'],
            [
                'nama_pesantren'      => 'Pesantren Demo',
                'paket_langganan'     => 'akselerasi',
                'max_santri_kuota'    => 500,
                'status_berlangganan' => 'active',
                'expired_at'          => now()->addYear(),
            ]
        );

        // --- 2. Admin Pesantren ---
        $admin = User::updateOrCreate(
            ['email' => 'admin@pesantren-demo.com'],
            [
                'pesantren_id' => $pesantren->id,
                'name'         => 'Admin Demo',
                'phone_number' => '081200000001',
                'password'     => 'admin123',
                'role'         => 'admin_pesantren',
            ]
        );

        // --- 3. Ustadz ---
        $ustadz = User::updateOrCreate(
            ['email' => 'ustadz@pesantren-demo.com'],
            [
                'pesantren_id' => $pesantren->id,
                'name'         => 'Ustadz Hasan',
                'phone_number' => '081200000002',
                'password'     => 'ustadz123',
                'role'         => 'ustadz',
            ]
        );

        // --- 4. Wali Santri ---
        $wali = User::updateOrCreate(
            ['email' => 'wali@pesantren-demo.com'],
            [
                'pesantren_id' => $pesantren->id,
                'name'         => 'Bapak Fulan',
                'phone_number' => '081200000003',
                'password'     => 'wali123',
                'role'         => 'wali_santri',
            ]
        );

        // --- 5. Santri (2 anak, 1 wali, 1 ustadz) ---
        // Global Scope tidak aktif di Seeder (tidak ada auth user),
        // jadi pesantren_id wajib diisi eksplisit.

        Santri::updateOrCreate(
            ['nis' => 'NIS-001', 'pesantren_id' => $pesantren->id],
            [
                'pesantren_id'        => $pesantren->id,
                'wali_santri_id'      => $wali->id,
                'pembimbing_ustadz_id'=> $ustadz->id,
                'nama_lengkap'        => 'Ahmad Fulan',
                'kelas'               => 'Kelas 1',
                'kamar'               => 'Kamar Al-Fatih',
                'status_aktif'        => true,
            ]
        );

        Santri::updateOrCreate(
            ['nis' => 'NIS-002', 'pesantren_id' => $pesantren->id],
            [
                'pesantren_id'        => $pesantren->id,
                'wali_santri_id'      => $wali->id,
                'pembimbing_ustadz_id'=> $ustadz->id,
                'nama_lengkap'        => 'Yusuf Fulan',
                'kelas'               => 'Kelas 1',
                'kamar'               => 'Kamar Al-Fatih',
                'status_aktif'        => true,
            ]
        );
    }
}
