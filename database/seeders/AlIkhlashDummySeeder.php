<?php

namespace Database\Seeders;

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\KesantrianInventaris;
use App\Models\KesantrianKarakterRapor;
use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\EkskulMaster;
use App\Models\MasterPengumuman;
use App\Models\MataPelajaran;
use App\Models\SantriEkskul;
use App\Models\NilaiAkademik;
use App\Models\PembayaranSpp;
use App\Models\Pesantren;
use App\Models\PrestasiSantri;
use App\Models\Santri;
use App\Models\TagihanSpp;
use App\Models\TahfidzProgress;
use App\Models\TahfidzUjian;
use App\Models\User;
use App\Services\TahunAjaranOptions;
use Illuminate\Database\Seeder;

/**
 * Mengisi data dummy lengkap (santri, tahfidz, mutaba'ah, kesehatan, karakter,
 * inventaris, prestasi, akademik, SPP, pengumuman) untuk satu pesantren nyata
 * ("Al Ikhlash") yang sedang diisi manual, supaya semua menu bisa langsung
 * dieksplorasi tanpa input satu per satu.
 *
 * Jalankan: php artisan db:seed --class=AlIkhlashDummySeeder
 */
class AlIkhlashDummySeeder extends Seeder
{
    private Pesantren $pesantren;


    public function run(): void
    {
        $this->pesantren = Pesantren::withoutGlobalScope('pesantren')
            ->where('slug', 'alikhlash')->firstOrFail();

        $kelasList   = $this->seedKelas();
        $kamarList   = $this->seedKamar();
        $ustadzList  = $this->seedUstadz();
        $waliList    = $this->seedWali();
        $santriList  = $this->seedSantri($kelasList, $kamarList, $ustadzList, $waliList);
        $mapelList   = $this->seedMataPelajaran($kelasList, $ustadzList);
        $ekskulList  = $this->seedEkskulMaster();

        foreach ($santriList as $i => $santri) {
            $this->seedTahfidzProgress($santri);
            $this->seedTahfidzRapor($santri, $ustadzList);
            $this->seedMutabaah($santri);
            $this->seedKesehatan($santri);
            $this->seedKarakterRapor($santri);
            $this->seedInventaris($santri);
            if ($i % 2 === 0) {
                $this->seedPrestasi($santri);
            }
            $this->seedSpp($santri);
            $this->seedNilaiAkademik($santri, $mapelList);
            $this->seedSantriEkskul($santri, $ekskulList);
        }

        $this->seedPengumuman();

        $this->pesantren->update(['santri_count_cache' => count($santriList)]);
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedKelas(): array
    {
        return collect(['Kelas 1 Tsanawi', 'Kelas 2 Tsanawi'])
            ->map(fn ($nama) => Kelas::updateOrCreate(
                ['pesantren_id' => $this->pesantren->id, 'nama_kelas' => $nama],
                ['pesantren_id' => $this->pesantren->id, 'nama_kelas' => $nama],
            ))
            ->all();
    }

    private function seedKamar(): array
    {
        return collect(['Kamar Ar-Rahman', 'Kamar Al-Amin'])
            ->map(fn ($nama) => Kamar::updateOrCreate(
                ['pesantren_id' => $this->pesantren->id, 'nama_kamar' => $nama],
                ['pesantren_id' => $this->pesantren->id, 'nama_kamar' => $nama, 'kapasitas' => 8],
            ))
            ->all();
    }

    private function seedUstadz(): array
    {
        $data = [
            ['email' => 'ustadz.fahmi@alikhlash.com',    'name' => 'Ust. Fahmi Aziz Nasution'],
            ['email' => 'ustadz.zulkifli@alikhlash.com',  'name' => 'Ust. Zulkifli Hasan Putra'],
        ];

        return collect($data)->map(fn ($u, $i) => User::updateOrCreate(
            ['email' => $u['email']],
            [
                'pesantren_id' => $this->pesantren->id,
                'name'         => $u['name'],
                'phone_number' => '08134000' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'password'     => 'ustadz123',
                'role'         => 'ustadz',
            ]
        ))->all();
    }

    private function seedWali(): array
    {
        $data = [
            ['email' => 'wali.rahman@alikhlash.com',  'name' => 'Bapak Abdul Rahman Saputra'],
            ['email' => 'wali.kurniawan@alikhlash.com','name' => 'Bapak Kurniawan Hidayat'],
            ['email' => 'wali.maulana@alikhlash.com',  'name' => 'Bapak Maulana Yusuf'],
            ['email' => 'wali.santoso@alikhlash.com',  'name' => 'Bapak Heri Santoso'],
            ['email' => 'wali.gunawan@alikhlash.com',  'name' => 'Bapak Gunawan Pratama'],
            ['email' => 'wali.rizki@alikhlash.com',    'name' => 'Bapak Rizki Ramadhan'],
        ];

        return collect($data)->map(fn ($w, $i) => User::updateOrCreate(
            ['email' => $w['email']],
            [
                'pesantren_id' => $this->pesantren->id,
                'name'         => $w['name'],
                'phone_number' => '08215000' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'password'     => 'wali123',
                'role'         => 'wali_santri',
            ]
        ))->all();
    }

    /** @return Santri[] */
    private function seedSantri(array $kelasList, array $kamarList, array $ustadzList, array $waliList): array
    {
        // Distribusi anak per wali: 1,1,1,2,2,3 = 10 santri dari 6 wali
        // (sengaja dibuat variatif: ada wali 1 anak, 2 anak, dan 3 anak)
        $waliPerSantri = [0, 1, 2, 3, 3, 4, 4, 5, 5, 5];

        $namaSantri = [
            'Ahmad Zaki Maulana', 'Fadhil Rabbani Hakim', 'Hilmi Akbar Nugraha',
            'Irfan Maulidi Saputra', 'Naufal Aziz Pratama', 'Rizqi Fadhlan Ramadhan',
            'Yusuf Abdullah Firmansyah', 'Daffa Rasyid Wibowo', 'Faiz Al-Ghazali Nugroho',
            'Haikal Ar-Rasyid Pranata',
        ];

        $citaCita = ['Hafidz Quran', 'Ustadz', 'Dokter', 'Pengusaha', 'Guru', 'Insinyur', 'Pilot', 'Polisi'];
        $ciriFisik = [
            'Berkacamata, tinggi sedang', 'Berkulit sawo matang, rambut keriting',
            'Bertubuh kurus, suara lantang', 'Memiliki tanda lahir di tangan kiri',
            'Bertubuh tinggi, kulit putih', 'Berperawakan gemuk, ramah',
        ];

        $santriList = [];
        foreach ($namaSantri as $i => $nama) {
            $waliIdx = $waliPerSantri[$i];
            $kelas   = $kelasList[$i % count($kelasList)];
            $kamar   = $kamarList[$i % count($kamarList)];
            $ustadz  = $ustadzList[$i % count($ustadzList)];
            $umur    = rand(10, 15);

            $santriList[] = Santri::updateOrCreate(
                ['nis' => 'AIK-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), 'pesantren_id' => $this->pesantren->id],
                [
                    'pesantren_id'         => $this->pesantren->id,
                    'wali_santri_id'       => $waliList[$waliIdx]->id,
                    'pembimbing_ustadz_id' => $ustadz->id,
                    'nama_lengkap'         => $nama,
                    'nama_panggilan'       => explode(' ', $nama)[0],
                    'tanggal_lahir'        => now()->subYears($umur)->subDays(rand(0, 364)),
                    'nama_ayah'            => $waliList[$waliIdx]->name,
                    'nama_ibu'             => 'Ibu ' . fake()->firstName('female'),
                    'alamat_lengkap'       => fake()->streetAddress() . ', ' . fake()->city() . ', Jawa Barat',
                    'jumlah_saudara'       => rand(0, 4),
                    'ciri_fisik'           => $ciriFisik[$i % count($ciriFisik)],
                    'cita_cita'            => $citaCita[$i % count($citaCita)],
                    'kelas_id'             => $kelas->id,
                    'kamar_id'             => $kamar->id,
                    'status_aktif'         => true,
                ]
            );
        }

        return $santriList;
    }

    private function seedMataPelajaran(array $kelasList, array $ustadzList): array
    {
        $mapelNames = ['Bahasa Arab', 'Fiqih', 'Aqidah Akhlak'];

        $mapelList = [];
        foreach ($kelasList as $kelas) {
            foreach ($mapelNames as $j => $nama) {
                $mapelList[$kelas->id][] = MataPelajaran::firstOrCreate(
                    ['pesantren_id' => $this->pesantren->id, 'kelas_id' => $kelas->id, 'nama_mapel' => $nama],
                    [
                        'pesantren_id' => $this->pesantren->id,
                        'kelas_id'     => $kelas->id,
                        'ustadz_id'    => $ustadzList[$j % count($ustadzList)]->id,
                        'nama_mapel'   => $nama,
                    ]
                );
            }
        }

        return $mapelList;
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedTahfidzProgress(Santri $santri): void
    {
        if (TahfidzProgress::where('santri_id', $santri->id)->exists()) {
            return;
        }

        $tipeSetoran = ['Sabaq', 'Sabqi', 'Manzil'];
        $nilaiList   = ['Mumtaz', 'Jayyid Jiddan', 'Jayyid', 'Maqbul'];

        // Range halaman realistis: Juz 28–30 (hal. 541–600)
        $baseRanges = [
            [591, 600], [581, 590], [571, 580], [561, 570], [551, 560], [541, 550],
        ];

        for ($i = 0; $i < 6; $i++) {
            [$start, $end] = $baseRanges[$i];

            TahfidzProgress::create([
                'pesantren_id'     => $this->pesantren->id,
                'santri_id'        => $santri->id,
                'ustadz_id'        => $santri->pembimbing_ustadz_id,
                'tanggal'          => now()->subDays($i * 4 + rand(0, 2)),
                'tipe_setoran'     => $tipeSetoran[array_rand($tipeSetoran)],
                'halaman_mulai'    => $start,
                'halaman_selesai'  => $end,
                'nama_surah'       => null,
                'nilai_kelancaran' => $nilaiList[array_rand($nilaiList)],
                'catatan_evaluasi' => 'Perlu lebih diperhatikan panjang bacaan dan tajwid.',
            ]);
        }
    }

    private function seedTahfidzRapor(Santri $santri, array $ustadzList): void
    {
        if (TahfidzUjian::where('santri_id', $santri->id)->exists()) {
            return;
        }

        $grade = ['A', 'B', 'C'];

        TahfidzUjian::create([
            'pesantren_id'           => $this->pesantren->id,
            'santri_id'              => $santri->id,
            'penguji_id'             => $santri->pembimbing_ustadz_id,
            'tanggal_ujian'          => now()->subDays(rand(2, 10)),
            'target_juz'             => rand(1, 5),
            'status_kelulusan'       => 'Lulus',
            'tahun_ajaran'           => TahunAjaranOptions::current(),
            'periode'                => 'Semester_Ganjil',
            'nilai_hafalan'          => (string) rand(75, 95),
            'nilai_tilawah'          => $grade[array_rand($grade)],
            'nilai_makhraj'          => $grade[array_rand($grade)],
            'nilai_tajwid'           => $grade[array_rand($grade)],
            'rekomendasi_pembimbing' => 'Tingkatkan murajaah harian agar hafalan lebih kuat.',
        ]);
    }

    private function seedMutabaah(Santri $santri): void
    {
        $udzurOptions = ['Tidak', 'Tidak', 'Tidak', 'Tidak', 'Sakit', 'Izin_Pulang'];

        for ($i = 0; $i < 14; $i++) {
            $tanggal = now()->subDays($i);
            $udzur   = $udzurOptions[array_rand($udzurOptions)];

            $amalan = $udzur === 'Tidak' ? [
                'jamaah_5_waktu'  => rand(2, 5),
                'is_rawatib'      => (bool) rand(0, 1),
                'is_shalat_malam' => (bool) rand(0, 1),
                'is_dhuha'        => (bool) rand(0, 1),
                'is_tilawah_1juz' => (bool) rand(0, 1),
                'is_infak'        => (bool) rand(0, 1),
                'is_puasa'        => rand(0, 4) === 0,
            ] : [
                'jamaah_5_waktu'  => 0,
                'is_rawatib'      => false,
                'is_shalat_malam' => false,
                'is_dhuha'        => false,
                'is_tilawah_1juz' => false,
                'is_infak'        => false,
                'is_puasa'        => false,
            ];

            KesantrianMutabaah::updateOrCreate(
                ['santri_id' => $santri->id, 'tanggal' => $tanggal->toDateString()],
                [
                    'pesantren_id' => $this->pesantren->id,
                    'amalan'       => $amalan,
                    'status_udzur' => $udzur,
                ]
            );
        }
    }

    private function seedKesehatan(Santri $santri): void
    {
        if (KesantrianKesehatan::where('santri_id', $santri->id)->exists()) {
            return;
        }

        $kategori = ['Demam', 'Batuk_Pilek', 'Sakit_Perut', 'Pusing', 'Kulit_Gatal'];
        $status   = ['Rawat_Mandiri', 'Rawat_Mandiri', 'Istirahat_Total'];

        KesantrianKesehatan::create([
            'pesantren_id'        => $this->pesantren->id,
            'santri_id'           => $santri->id,
            'tanggal_periksa'     => now()->subDays(rand(1, 20)),
            'berat_badan'         => rand(30, 55),
            'tinggi_badan'        => rand(130, 165),
            'kategori_keluhan'    => $kategori[array_rand($kategori)],
            'detail_keluhan_teks' => 'Mengeluh tidak enak badan sejak semalam.',
            'tindakan_dan_obat'   => 'Diberi obat dari UKS dan istirahat.',
            'status_pemulihan'    => $status[array_rand($status)],
        ]);
    }

    private function seedKarakterRapor(Santri $santri): void
    {
        if (KesantrianKarakterRapor::where('santri_id', $santri->id)->exists()) {
            return;
        }

        $grade = ['A', 'B', 'B', 'C'];
        $pick  = fn () => $grade[array_rand($grade)];

        KesantrianKarakterRapor::create([
            'pesantren_id'              => $this->pesantren->id,
            'santri_id'                 => $santri->id,
            'periode'                   => 'Bulanan',
            'tanggal_input'             => now()->subDays(rand(1, 15)),
            'adab_ustadz'               => $pick(),
            'adab_tamu'                 => $pick(),
            'adab_asrama'               => $pick(),
            'adab_kelas'                => $pick(),
            'adab_sholat'               => $pick(),
            'adab_quran'                => $pick(),
            'adab_minum'                => $pick(),
            'kepribadian_tanggungjawab' => $pick(),
            'kepribadian_kemandirian'   => $pick(),
            'kepribadian_kepatuhan'     => $pick(),
            'kepribadian_kebersihan'    => $pick(),
            'kepribadian_mengelola'     => $pick(),
            'kepribadian_kepedulian'    => $pick(),
            'kepribadian_empati'        => $pick(),
            'kepribadian_kebersamaan'   => $pick(),
            'kepribadian_kedisiplinan'  => $pick(),
            'log_kasus_khusus'          => null,
        ]);
    }

    private function seedInventaris(Santri $santri): void
    {
        if (KesantrianInventaris::where('santri_id', $santri->id)->exists()) {
            return;
        }

        $barang = ['Sarung', 'Handuk', 'Sajadah', 'Baju Koko'];
        $kondisi = ['Baik', 'Baik', 'Layak_Rusak'];

        foreach (array_slice($barang, 0, rand(1, 2)) as $j => $nama) {
            KesantrianInventaris::create([
                'pesantren_id'            => $this->pesantren->id,
                'santri_id'               => $santri->id,
                'nama_barang_umum'        => $nama,
                'kode_unik_fisik'         => 'AIK-' . $santri->id . '-' . ($j + 1),
                'kuota_regulasi_maksimal' => rand(1, 3),
                'kondisi_barang'          => $kondisi[array_rand($kondisi)],
                'tanggal_sidak_terakhir'  => now()->subDays(rand(1, 30)),
            ]);
        }
    }

    private function seedPrestasi(Santri $santri): void
    {
        if (PrestasiSantri::where('santri_id', $santri->id)->exists()) {
            return;
        }

        $kategori = array_keys(PrestasiSantri::$kategoriOptions);
        $posisi   = array_keys(PrestasiSantri::$posisiOptions);
        $tingkat  = ['internal', 'kabupaten', 'provinsi'];

        PrestasiSantri::create([
            'pesantren_id'  => $this->pesantren->id,
            'santri_id'     => $santri->id,
            'judul'         => 'Juara Lomba ' . $kategori[array_rand($kategori)],
            'kategori'      => $kategori[array_rand($kategori)],
            'tingkat'       => $tingkat[array_rand($tingkat)],
            'posisi'        => $posisi[array_rand($posisi)],
            'tanggal'       => now()->subDays(rand(5, 60)),
            'penyelenggara' => 'Kemenag Kabupaten/Kota',
            'keterangan'    => null,
        ]);
    }

    private function seedSpp(Santri $santri): void
    {
        $nominal = 300_000;

        for ($i = 2; $i >= 0; $i--) {
            $bulanDate = now()->subMonths($i);
            $isLunas   = $i > 0; // bulan-bulan lalu lunas, bulan ini belum bayar

            $tagihan = TagihanSpp::updateOrCreate(
                [
                    'pesantren_id' => $this->pesantren->id,
                    'santri_id'    => $santri->id,
                    'bulan'        => $bulanDate->month,
                    'tahun'        => $bulanDate->year,
                ],
                [
                    'nominal'     => $nominal,
                    'jatuh_tempo' => $bulanDate->copy()->startOfMonth()->addDays(9),
                    'keterangan'  => 'SPP Bulanan',
                    'status'      => $isLunas ? 'lunas' : 'belum_bayar',
                ]
            );

            if ($isLunas && ! PembayaranSpp::where('tagihan_spp_id', $tagihan->id)->exists()) {
                $admin = User::where('pesantren_id', $this->pesantren->id)
                    ->where('role', 'admin_pesantren')->first();

                PembayaranSpp::create([
                    'pesantren_id'   => $this->pesantren->id,
                    'tagihan_spp_id' => $tagihan->id,
                    'jumlah'         => $nominal,
                    'tanggal_bayar'  => $bulanDate->copy()->startOfMonth()->addDays(5),
                    'metode_bayar'   => 'transfer_bank',
                    'catatan'        => null,
                    'dicatat_oleh'   => $admin?->id,
                ]);
            }
        }
    }

    private function seedNilaiAkademik(Santri $santri, array $mapelList): void
    {
        if (NilaiAkademik::where('santri_id', $santri->id)->exists()) {
            return;
        }

        $mapelForKelas = $mapelList[$santri->kelas_id] ?? [];

        foreach ($mapelForKelas as $mapel) {
            NilaiAkademik::create([
                'pesantren_id'      => $this->pesantren->id,
                'santri_id'         => $santri->id,
                'mata_pelajaran_id' => $mapel->id,
                'tahun_ajaran'      => TahunAjaranOptions::current(),
                'periode'           => 'Bulanan',
                'nilai'             => rand(70, 95),
                'catatan'           => null,
            ]);
        }
    }

    /** @return EkskulMaster[] */
    private function seedEkskulMaster(): array
    {
        $ekskuls = [
            'Berenang', 'Berkuda', 'Memanah', 'Bela Diri', 'Tata Boga',
            'Tata Busana', 'Handicraft', 'Muhadhoroh', 'Kaligrafi',
            'Nasyid', 'Animasi', 'Pramuka/Kepanduan', 'Sapala', 'Leadership',
        ];

        return collect($ekskuls)->map(fn ($nama) => EkskulMaster::firstOrCreate(
            ['pesantren_id' => $this->pesantren->id, 'nama' => $nama],
            ['pesantren_id' => $this->pesantren->id, 'nama' => $nama, 'aktif' => true],
        ))->all();
    }

    private function seedSantriEkskul(Santri $santri, array $ekskulList): void
    {
        if (SantriEkskul::where('santri_id', $santri->id)->exists()) {
            return;
        }

        $levels  = ['pemula', 'pemula', 'menengah', 'mahir'];
        $pilihan = array_rand(array_fill(0, count($ekskulList), null), rand(2, 4));

        foreach ((array) $pilihan as $idx) {
            SantriEkskul::firstOrCreate(
                ['santri_id' => $santri->id, 'ekskul_id' => $ekskulList[$idx]->id],
                [
                    'pesantren_id'  => $this->pesantren->id,
                    'level'         => $levels[array_rand($levels)],
                    'tanggal_mulai' => now()->subMonths(rand(1, 6)),
                    'aktif'         => true,
                ]
            );
        }
    }

    private function seedPengumuman(): void
    {
        $items = [
            [
                'judul' => 'Libur Semester Ganjil',
                'isi'   => '<p>Diberitahukan kepada seluruh wali santri bahwa libur semester ganjil akan dimulai minggu depan.</p>',
                'target'=> 'semua',
            ],
            [
                'judul' => 'Pembayaran SPP Bulan Ini',
                'isi'   => '<p>Mohon segera melunasi SPP bulan ini melalui transfer bank dan konfirmasi via portal wali.</p>',
                'target'=> 'wali',
            ],
        ];

        foreach ($items as $item) {
            MasterPengumuman::firstOrCreate(
                ['pesantren_id' => $this->pesantren->id, 'judul_maklumat' => $item['judul']],
                [
                    'pesantren_id'    => $this->pesantren->id,
                    'judul_maklumat'  => $item['judul'],
                    'isi_maklumat'    => $item['isi'],
                    'target_audience' => $item['target'],
                ]
            );
        }
    }
}
