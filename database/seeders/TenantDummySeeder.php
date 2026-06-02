<?php

namespace Database\Seeders;

use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\TenantDomain;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantDummySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPesantrenAlFatah();
        $this->seedPesantrenIbnuHajar();
        $this->seedPesantrenDarulIlmi();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pesantren 1 — Al-Fatah (Rintisan, Active) — 10 santri
    // ─────────────────────────────────────────────────────────────────────────
    private function seedPesantrenAlFatah(): void
    {
        $pesantren = Pesantren::updateOrCreate(
            ['slug' => 'pesantren-al-fatah'],
            [
                'nama_pesantren'      => 'Pesantren Al-Fatah',
                'paket_langganan'     => 'rintisan',
                'max_santri_kuota'    => 100,
                'status_berlangganan' => 'active',
                'expired_at'          => now()->addYear(),
                'profil' => [
                    'alamat'    => 'Jl. Pondok Pesantren No. 1, Bogor, Jawa Barat',
                    'telepon'   => '0251-123456',
                    'deskripsi' => 'Pesantren modern dengan kurikulum tahfidz dan sains.',
                ],
            ]
        );

        User::updateOrCreate(['email' => 'admin@al-fatah.com'], [
            'pesantren_id' => $pesantren->id,
            'name'         => 'H. Ahmad Fauzi',
            'phone_number' => '081311111001',
            'password'     => 'admin123',
            'role'         => 'admin_pesantren',
        ]);

        $ustadz1 = User::updateOrCreate(['email' => 'ustadz.ibrahim@al-fatah.com'], [
            'pesantren_id' => $pesantren->id,
            'name'         => 'Ust. Ibrahim Al-Hafidzh',
            'phone_number' => '081311111002',
            'password'     => 'ustadz123',
            'role'         => 'ustadz',
        ]);

        $ustadz2 = User::updateOrCreate(['email' => 'ustadz.yusuf@al-fatah.com'], [
            'pesantren_id' => $pesantren->id,
            'name'         => 'Ust. Yusuf Mansur',
            'phone_number' => '081311111003',
            'password'     => 'ustadz123',
            'role'         => 'ustadz',
        ]);

        $waliData = [
            ['email' => 'wali.fulan@al-fatah.com',  'name' => 'Bapak Muhammad Fulan',  'phone' => '082211111001'],
            ['email' => 'wali.hasan@al-fatah.com',  'name' => 'Bapak Hasan Basri',     'phone' => '082211111002'],
            ['email' => 'wali.zainal@al-fatah.com', 'name' => 'Bapak Zainal Abidin',   'phone' => '082211111003'],
            ['email' => 'wali.ridwan@al-fatah.com', 'name' => 'Bapak Ridwan Khalil',   'phone' => '082211111004'],
            ['email' => 'wali.sholeh@al-fatah.com', 'name' => 'Bapak Sholeh Wahyudi', 'phone' => '082211111005'],
        ];

        $waliIds = [];
        foreach ($waliData as $w) {
            $wali = User::updateOrCreate(['email' => $w['email']], [
                'pesantren_id' => $pesantren->id,
                'name'         => $w['name'],
                'phone_number' => $w['phone'],
                'password'     => 'wali123',
                'role'         => 'wali_santri',
            ]);
            $waliIds[] = $wali->id;
        }

        $santriData = [
            ['nis' => 'ALF-001', 'nama' => 'Ahmad Fathi Mubarak',    'kelas' => 'Kelas 1 Ibtida',  'kamar' => 'Kamar Al-Fatih', 'wali' => 0, 'ustadz' => $ustadz1->id],
            ['nis' => 'ALF-002', 'nama' => 'Fathurrahman Habibi',    'kelas' => 'Kelas 1 Ibtida',  'kamar' => 'Kamar Al-Fatih', 'wali' => 0, 'ustadz' => $ustadz1->id],
            ['nis' => 'ALF-003', 'nama' => 'Abdullah Hasanuddin',    'kelas' => 'Kelas 1 Ibtida',  'kamar' => 'Kamar Khalid',   'wali' => 1, 'ustadz' => $ustadz1->id],
            ['nis' => 'ALF-004', 'nama' => 'Muhammad Rasyid Ridha',  'kelas' => 'Kelas 2 Ibtida',  'kamar' => 'Kamar Khalid',   'wali' => 1, 'ustadz' => $ustadz2->id],
            ['nis' => 'ALF-005', 'nama' => 'Ibrahim Khalil Rahman',  'kelas' => 'Kelas 2 Ibtida',  'kamar' => 'Kamar Umar',     'wali' => 2, 'ustadz' => $ustadz2->id],
            ['nis' => 'ALF-006', 'nama' => 'Yusuf Al-Karim Jazuli',  'kelas' => 'Kelas 2 Ibtida',  'kamar' => 'Kamar Umar',     'wali' => 2, 'ustadz' => $ustadz1->id],
            ['nis' => 'ALF-007', 'nama' => 'Umar Faruq Rosyidi',     'kelas' => 'Kelas 1 Tsanawi', 'kamar' => 'Kamar Ali',      'wali' => 3, 'ustadz' => $ustadz2->id],
            ['nis' => 'ALF-008', 'nama' => 'Utsman Ghani Saputra',   'kelas' => 'Kelas 1 Tsanawi', 'kamar' => 'Kamar Ali',      'wali' => 3, 'ustadz' => $ustadz1->id],
            ['nis' => 'ALF-009', 'nama' => 'Ali Haidar Muqaddam',    'kelas' => 'Kelas 2 Tsanawi', 'kamar' => 'Kamar Abu Bakar','wali' => 4, 'ustadz' => $ustadz2->id],
            ['nis' => 'ALF-010', 'nama' => 'Hasan Noorani Fahmi',    'kelas' => 'Kelas 2 Tsanawi', 'kamar' => 'Kamar Abu Bakar','wali' => 4, 'ustadz' => $ustadz2->id],
        ];

        foreach ($santriData as $s) {
            Santri::updateOrCreate(
                ['nis' => $s['nis'], 'pesantren_id' => $pesantren->id],
                [
                    'pesantren_id'         => $pesantren->id,
                    'wali_santri_id'       => $waliIds[$s['wali']],
                    'pembimbing_ustadz_id' => $s['ustadz'],
                    'nama_lengkap'         => $s['nama'],
                    'kelas'                => $s['kelas'],
                    'kamar'                => $s['kamar'],
                    'status_aktif'         => true,
                ]
            );
        }

        TenantDomain::updateOrCreate(
            ['hostname' => 'pesantren-al-fatah.walisantri.test'],
            [
                'pesantren_id' => $pesantren->id,
                'type'         => 'subdomain',
                'is_primary'   => true,
                'verified_at'  => now(),
                'ssl_status'   => 'active',
            ]
        );

        $pesantren->update(['santri_count_cache' => count($santriData)]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pesantren 2 — Ibnu Hajar (Berkembang, Active) — 15 santri
    // ─────────────────────────────────────────────────────────────────────────
    private function seedPesantrenIbnuHajar(): void
    {
        $pesantren = Pesantren::updateOrCreate(
            ['slug' => 'pesantren-ibnu-hajar'],
            [
                'nama_pesantren'      => 'Pesantren Ibnu Hajar',
                'paket_langganan'     => 'berkembang',
                'max_santri_kuota'    => 500,
                'status_berlangganan' => 'active',
                'expired_at'          => now()->addMonths(8),
                'profil' => [
                    'alamat'    => 'Jl. K.H. Hasyim Ashari No. 55, Bekasi, Jawa Barat',
                    'telepon'   => '021-8765432',
                    'deskripsi' => 'Pesantren tahfidz Al-Quran dengan metode Ummi dan Murojaah terstruktur.',
                ],
            ]
        );

        User::updateOrCreate(['email' => 'admin@ibnu-hajar.com'], [
            'pesantren_id' => $pesantren->id,
            'name'         => 'Ust. Mukhlis Anwar',
            'phone_number' => '081322222001',
            'password'     => 'admin123',
            'role'         => 'admin_pesantren',
        ]);

        $ust1 = User::updateOrCreate(['email' => 'ustadz.hamid@ibnu-hajar.com'], [
            'pesantren_id' => $pesantren->id,
            'name'         => 'Ust. Abdul Hamid Nawawi',
            'phone_number' => '081322222002',
            'password'     => 'ustadz123',
            'role'         => 'ustadz',
        ]);

        $ust2 = User::updateOrCreate(['email' => 'ustadz.ismail@ibnu-hajar.com'], [
            'pesantren_id' => $pesantren->id,
            'name'         => 'Ust. Ismail Zuhri',
            'phone_number' => '081322222003',
            'password'     => 'ustadz123',
            'role'         => 'ustadz',
        ]);

        $ust3 = User::updateOrCreate(['email' => 'ustadz.lukman@ibnu-hajar.com'], [
            'pesantren_id' => $pesantren->id,
            'name'         => 'Ust. Luqman Hakim',
            'phone_number' => '081322222004',
            'password'     => 'ustadz123',
            'role'         => 'ustadz',
        ]);

        $waliData = [
            ['email' => 'wali.bambang@ibnu-hajar.com',  'name' => 'Bapak Bambang Sudarsono',  'phone' => '083333331001'],
            ['email' => 'wali.santoso@ibnu-hajar.com',  'name' => 'Bapak Santoso Wibowo',     'phone' => '083333331002'],
            ['email' => 'wali.surya@ibnu-hajar.com',    'name' => 'Bapak Surya Dharma',       'phone' => '083333331003'],
            ['email' => 'wali.agus@ibnu-hajar.com',     'name' => 'Bapak Agus Salim',         'phone' => '083333331004'],
            ['email' => 'wali.budi@ibnu-hajar.com',     'name' => 'Bapak Budi Santoso',       'phone' => '083333331005'],
            ['email' => 'wali.dedi@ibnu-hajar.com',     'name' => 'Bapak Dedi Mulyadi',       'phone' => '083333331006'],
            ['email' => 'wali.eko@ibnu-hajar.com',      'name' => 'Bapak Eko Prasetyo',       'phone' => '083333331007'],
            ['email' => 'wali.firmansyah@ibnu-hajar.com','name' => 'Bapak Firmansyah Putra',  'phone' => '083333331008'],
        ];

        $waliIds = [];
        foreach ($waliData as $w) {
            $wali = User::updateOrCreate(['email' => $w['email']], [
                'pesantren_id' => $pesantren->id,
                'name'         => $w['name'],
                'phone_number' => $w['phone'],
                'password'     => 'wali123',
                'role'         => 'wali_santri',
            ]);
            $waliIds[] = $wali->id;
        }

        $santriData = [
            ['nis' => 'IH-001', 'nama' => 'Zaid bin Tsabit Santoso',    'kelas' => 'Kelas 1 Ula',   'kamar' => 'Kamar As-Siddiq', 'wali' => 0, 'ust' => $ust1->id],
            ['nis' => 'IH-002', 'nama' => 'Bilal Muadzin Wibowo',       'kelas' => 'Kelas 1 Ula',   'kamar' => 'Kamar As-Siddiq', 'wali' => 0, 'ust' => $ust1->id],
            ['nis' => 'IH-003', 'nama' => 'Salman Al-Farisi Dharma',    'kelas' => 'Kelas 1 Ula',   'kamar' => 'Kamar Al-Faruq',  'wali' => 1, 'ust' => $ust1->id],
            ['nis' => 'IH-004', 'nama' => 'Ammar Yasir Salim',          'kelas' => 'Kelas 1 Ula',   'kamar' => 'Kamar Al-Faruq',  'wali' => 2, 'ust' => $ust2->id],
            ['nis' => 'IH-005', 'nama' => 'Khabbab bin Al-Arat',        'kelas' => 'Kelas 2 Ula',   'kamar' => 'Kamar Dzun Nurain','wali' => 2, 'ust' => $ust2->id],
            ['nis' => 'IH-006', 'nama' => 'Mus\'ab bin Umair Santoso',  'kelas' => 'Kelas 2 Ula',   'kamar' => 'Kamar Dzun Nurain','wali' => 3, 'ust' => $ust2->id],
            ['nis' => 'IH-007', 'nama' => 'Abdurrahman bin Awf Budi',   'kelas' => 'Kelas 2 Ula',   'kamar' => 'Kamar Abu Ubaidah','wali' => 3, 'ust' => $ust3->id],
            ['nis' => 'IH-008', 'nama' => 'Saad bin Abi Waqqas Mulyadi','kelas' => 'Kelas 3 Ula',   'kamar' => 'Kamar Abu Ubaidah','wali' => 4, 'ust' => $ust3->id],
            ['nis' => 'IH-009', 'nama' => 'Thalhah bin Ubaidillah',     'kelas' => 'Kelas 3 Ula',   'kamar' => 'Kamar Zubair',    'wali' => 4, 'ust' => $ust1->id],
            ['nis' => 'IH-010', 'nama' => 'Zubair bin Awwam Prasetyo',  'kelas' => 'Kelas 3 Ula',   'kamar' => 'Kamar Zubair',    'wali' => 5, 'ust' => $ust1->id],
            ['nis' => 'IH-011', 'nama' => 'Said bin Zaid Putra',        'kelas' => 'Kelas 1 Wustho','kamar' => 'Kamar Sa\'ad',    'wali' => 5, 'ust' => $ust2->id],
            ['nis' => 'IH-012', 'nama' => 'Hudzaifah bin Yaman',        'kelas' => 'Kelas 1 Wustho','kamar' => 'Kamar Sa\'ad',    'wali' => 6, 'ust' => $ust2->id],
            ['nis' => 'IH-013', 'nama' => 'Abu Hurairah Mulyono',       'kelas' => 'Kelas 2 Wustho','kamar' => 'Kamar Mush\'ab',  'wali' => 6, 'ust' => $ust3->id],
            ['nis' => 'IH-014', 'nama' => 'Jabir bin Abdullah Prasetyo','kelas' => 'Kelas 2 Wustho','kamar' => 'Kamar Mush\'ab',  'wali' => 7, 'ust' => $ust3->id],
            ['nis' => 'IH-015', 'nama' => 'Anas bin Malik Hidayat',     'kelas' => 'Kelas 2 Wustho','kamar' => 'Kamar Bilal',     'wali' => 7, 'ust' => $ust3->id],
        ];

        foreach ($santriData as $s) {
            Santri::updateOrCreate(
                ['nis' => $s['nis'], 'pesantren_id' => $pesantren->id],
                [
                    'pesantren_id'         => $pesantren->id,
                    'wali_santri_id'       => $waliIds[$s['wali']],
                    'pembimbing_ustadz_id' => $s['ust'],
                    'nama_lengkap'         => $s['nama'],
                    'kelas'                => $s['kelas'],
                    'kamar'                => $s['kamar'],
                    'status_aktif'         => true,
                ]
            );
        }

        TenantDomain::updateOrCreate(
            ['hostname' => 'pesantren-ibnu-hajar.walisantri.test'],
            [
                'pesantren_id' => $pesantren->id,
                'type'         => 'subdomain',
                'is_primary'   => true,
                'verified_at'  => now(),
                'ssl_status'   => 'active',
            ]
        );

        $pesantren->update(['santri_count_cache' => count($santriData)]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pesantren 3 — Darul Ilmi (Gratis, Trial) — 5 santri
    // ─────────────────────────────────────────────────────────────────────────
    private function seedPesantrenDarulIlmi(): void
    {
        $pesantren = Pesantren::updateOrCreate(
            ['slug' => 'pesantren-darul-ilmi'],
            [
                'nama_pesantren'      => 'Pesantren Darul Ilmi',
                'paket_langganan'     => 'gratis',
                'max_santri_kuota'    => 10,
                'status_berlangganan' => 'trial',
                'expired_at'          => now()->addDays(7),
                'profil' => [
                    'alamat'    => 'Jl. Pesantren Lama No. 12, Tasikmalaya, Jawa Barat',
                    'telepon'   => '0265-987654',
                    'deskripsi' => 'Pesantren rintisan yang baru terbentuk dengan program tahfidz dasar.',
                ],
            ]
        );

        User::updateOrCreate(['email' => 'admin@darul-ilmi.com'], [
            'pesantren_id' => $pesantren->id,
            'name'         => 'H. Syamsul Bahri',
            'phone_number' => '081344443001',
            'password'     => 'admin123',
            'role'         => 'admin_pesantren',
        ]);

        $ustadz = User::updateOrCreate(['email' => 'ustadz.ghazali@darul-ilmi.com'], [
            'pesantren_id' => $pesantren->id,
            'name'         => 'Ust. Ghazali Ar-Rasyid',
            'phone_number' => '081344443002',
            'password'     => 'ustadz123',
            'role'         => 'ustadz',
        ]);

        $waliData = [
            ['email' => 'wali.rohmat@darul-ilmi.com',  'name' => 'Bapak Rohmat Hidayat',  'phone' => '085555551001'],
            ['email' => 'wali.nurrohim@darul-ilmi.com','name' => 'Bapak Nurrohim Amin',   'phone' => '085555551002'],
            ['email' => 'wali.supardi@darul-ilmi.com', 'name' => 'Bapak Supardi Santun',  'phone' => '085555551003'],
        ];

        $waliIds = [];
        foreach ($waliData as $w) {
            $wali = User::updateOrCreate(['email' => $w['email']], [
                'pesantren_id' => $pesantren->id,
                'name'         => $w['name'],
                'phone_number' => $w['phone'],
                'password'     => 'wali123',
                'role'         => 'wali_santri',
            ]);
            $waliIds[] = $wali->id;
        }

        $santriData = [
            ['nis' => 'DI-001', 'nama' => 'Raihan Maulana Hidayat',  'kelas' => 'Halaqah 1', 'kamar' => 'Kamar Badr',   'wali' => 0],
            ['nis' => 'DI-002', 'nama' => 'Dzaki Fadhlurrahman',      'kelas' => 'Halaqah 1', 'kamar' => 'Kamar Badr',   'wali' => 0],
            ['nis' => 'DI-003', 'nama' => 'Farhan Ibnu Amin',         'kelas' => 'Halaqah 1', 'kamar' => 'Kamar Uhud',   'wali' => 1],
            ['nis' => 'DI-004', 'nama' => 'Ghifari Nurul Huda',       'kelas' => 'Halaqah 2', 'kamar' => 'Kamar Uhud',   'wali' => 1],
            ['nis' => 'DI-005', 'nama' => 'Hamdani Al-Faqih Santun',  'kelas' => 'Halaqah 2', 'kamar' => 'Kamar Hunain', 'wali' => 2],
        ];

        foreach ($santriData as $s) {
            Santri::updateOrCreate(
                ['nis' => $s['nis'], 'pesantren_id' => $pesantren->id],
                [
                    'pesantren_id'         => $pesantren->id,
                    'wali_santri_id'       => $waliIds[$s['wali']],
                    'pembimbing_ustadz_id' => $ustadz->id,
                    'nama_lengkap'         => $s['nama'],
                    'kelas'                => $s['kelas'],
                    'kamar'                => $s['kamar'],
                    'status_aktif'         => true,
                ]
            );
        }

        TenantDomain::updateOrCreate(
            ['hostname' => 'pesantren-darul-ilmi.walisantri.test'],
            [
                'pesantren_id' => $pesantren->id,
                'type'         => 'subdomain',
                'is_primary'   => true,
                'verified_at'  => now(),
                'ssl_status'   => 'pending',
            ]
        );

        $pesantren->update(['santri_count_cache' => count($santriData)]);
    }
}
