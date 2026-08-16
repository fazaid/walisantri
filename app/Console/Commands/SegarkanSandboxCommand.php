<?php

namespace App\Console\Commands;

use App\Enums\JenisUangSaku;
use App\Enums\StatusKehadiran;
use App\Enums\SumberPresensi;
use App\Models\EkskulMaster;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\KesantrianInventaris;
use App\Models\KesantrianKarakterRapor;
use App\Models\KesantrianKesehatan;
use App\Models\KesantrianMutabaah;
use App\Models\MasterPengumuman;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\PembayaranSpp;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\PrestasiSantri;
use App\Models\Santri;
use App\Models\SantriEkskul;
use App\Models\TagihanSpp;
use App\Models\TahfidzProgress;
use App\Models\TahfidzUjian;
use App\Models\UangSakuSantri;
use App\Models\User;
use App\Services\ProvisionTenant;
use App\Services\TahunAjaranOptions;
use App\Support\SandboxDemo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Membuat & menyegarkan tenant sandbox publik (demo.walisantri.com).
 *
 * Kenapa perintah, bukan seeder: datanya harus tetap terlihat HIDUP. Setoran,
 * presensi, dan tagihan SPP semuanya bertanggal; kalau di-seed sekali, sebulan
 * kemudian calon pelanggan membuka demo dan melihat produk yang tampak
 * ditinggalkan. Perintah ini dijadwalkan mingguan (routes/console.php) dan
 * menghitung ulang seluruh tanggal relatif terhadap hari ia dijalankan.
 *
 * Pembagian yang menentukan benar-tidaknya perintah ini:
 *
 * - Baris STRUKTURAL (pesantren, user, santri, kelas, kamar, mapel, ekskul)
 *   idempoten lewat updateOrCreate dan TIDAK PERNAH dibuat ulang. `santri.uuid`
 *   adalah token magic link (Santri::linkWali()) — membuat ulang santri akan
 *   mengganti uuid dan mematikan setiap tautan demo yang sudah dibagikan.
 * - Baris TRANSAKSIONAL (tahfidz, mutaba'ah, presensi, SPP, nilai, kesehatan,
 *   prestasi, uang saku, pengumuman) dihapus lalu ditulis ulang tiap kali jalan.
 *
 * Tenant ini lahir dengan expired_at = null, sehingga tidak pernah disentuh
 * SaaSLifecycleLock maupun ketiga job kedaluwarsa (semuanya mensyaratkan
 * whereNotNull('expired_at')), dan is_demo = true supaya tidak terhitung
 * sebagai pelanggan di dashboard super admin.
 *
 * Kontaknya sengaja tidak menjangkau manusia: email @demo.invalid (TLD cadangan
 * RFC 2606) dan phone_number null, jadi nol email/WA bisa terkirim gara-gara
 * tenant ini.
 */
class SegarkanSandboxCommand extends Command
{
    protected $signature = 'sandbox:segarkan {--dry-run : Tampilkan rencana tanpa menulis apa pun}';

    protected $description = 'Membuat atau menyegarkan tenant sandbox publik (demo.walisantri.com)';

    private const JUMLAH_SANTRI = 12;

    private const HARI_PRESENSI = 30;

    private const HARI_MUTABAAH = 21;

    private Pesantren $pesantren;

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            return $this->tampilkanRencana();
        }

        DB::transaction(function () {
            $this->pesantren = $this->pastikanPesantren();

            $kelas = $this->seedKelas();
            $kamar = $this->seedKamar();
            $ustadz = $this->seedUstadz();
            $wali = $this->seedWali();
            $santri = $this->seedSantri($kelas, $kamar, $ustadz, $wali);
            $mapel = $this->seedMataPelajaran($kelas, $ustadz);
            $ekskul = $this->seedEkskulMaster();

            $this->hapusDataTransaksional($santri);

            foreach ($santri as $i => $s) {
                $this->seedTahfidz($s, $ustadz);
                $this->seedMutabaah($s);
                $this->seedPresensi($s);
                $this->seedSpp($s);
                $this->seedNilaiAkademik($s, $mapel);
                $this->seedKesehatan($s);
                $this->seedKarakterRapor($s);
                $this->seedInventaris($s);
                $this->seedUangSaku($s);
                $this->seedSantriEkskul($s, $ekskul);

                if ($i % 3 === 0) {
                    $this->seedPrestasi($s);
                }
            }

            $this->seedPengumuman();

            $this->pesantren->update(['santri_count_cache' => count($santri)]);
        });

        SandboxDemo::lupakanCache();

        $contoh = $this->santriContoh();

        $this->info('Sandbox disegarkan.');
        $this->table(['Item', 'Nilai'], [
            ['Pesantren', $this->pesantren->nama_pesantren],
            ['Profil publik', 'https://'.SandboxDemo::SLUG.'.'.config('app.base_domain')],
            ['Santri', (string) $this->pesantren->santri()->count()],
            ['Portal wali contoh', $contoh?->linkWali() ?? '-'],
        ]);

        return self::SUCCESS;
    }

    private function tampilkanRencana(): int
    {
        $ada = Pesantren::where('slug', SandboxDemo::SLUG)->first();

        $this->info($ada
            ? "Tenant '".SandboxDemo::SLUG."' sudah ada (id {$ada->id}) — data transaksional akan dihapus lalu ditulis ulang."
            : "Tenant '".SandboxDemo::SLUG."' belum ada — akan dibuat beserta seluruh data contohnya.");

        $this->table(['Akan ditulis', 'Jumlah'], [
            ['Santri (stabil, uuid tidak berubah)', (string) self::JUMLAH_SANTRI],
            ['Hari presensi per santri', (string) self::HARI_PRESENSI],
            ['Hari mutaba\'ah per santri', (string) self::HARI_MUTABAAH],
        ]);

        $this->warn('Dry-run: tidak ada yang ditulis.');

        return self::SUCCESS;
    }

    private function santriContoh(): ?Santri
    {
        return SandboxDemo::santriContoh($this->pesantren);
    }

    // ─── Struktural (idempoten, tidak pernah dibuat ulang) ────────────────

    private function pastikanPesantren(): Pesantren
    {
        $pesantren = Pesantren::updateOrCreate(
            ['slug' => SandboxDemo::SLUG],
            [
                'nama_pesantren' => 'Pesantren Demo Walisantri',
                'is_demo' => true,
                'paket_langganan' => 'rintisan',
                // Longgar: kuota ditegakkan SantriObserver, jangan sampai
                // seeding ini mentok pada pagarnya sendiri.
                'max_santri_kuota' => 100,
                'status_berlangganan' => 'active',
                // Tidak pernah kedaluwarsa — lihat PHPDoc kelas.
                'expired_at' => null,
                'profil' => [
                    'deskripsi' => 'Pesantren contoh berisi data fiktif untuk memperlihatkan '
                        .'cara kerja Walisantri.com. Seluruh nama santri, wali, dan nilai di '
                        .'dalamnya dikarang — tidak ada data orang sungguhan di sini.',
                    'tahun_berdiri' => '2010',
                    'akreditasi' => 'A',
                    'telepon' => '021-0000000',
                    'email_kontak' => 'halo@demo.invalid',
                    // Bentuknya array of ['nama', 'jenjang'] — persis yang dibaca
                    // resources/views/public/profile.blade.php. Daftar string
                    // biasa akan merender kartu-kartu kosong.
                    'program' => [
                        ['nama' => 'Tahfidz Al-Quran 30 Juz', 'jenjang' => 'Semua jenjang'],
                        ['nama' => 'Madrasah Tsanawiyah', 'jenjang' => 'Kelas 1-3'],
                        ['nama' => 'Bahasa Arab & Inggris', 'jenjang' => 'Semua jenjang'],
                        ['nama' => 'Kepemimpinan & Kemandirian', 'jenjang' => 'Kelas 2-3'],
                    ],
                ],
            ]
        );

        // Mengisi tenant_domains (tanpanya demo.walisantri.com 404 selamanya),
        // amalan Mutaba'ah, pengaturan presensi, dan jam pelajaran. Idempoten.
        app(ProvisionTenant::class)->jalankan($pesantren);

        return $pesantren;
    }

    private function seedKelas(): array
    {
        return collect(['Kelas 1 Tsanawi', 'Kelas 2 Tsanawi', 'Kelas 3 Tsanawi'])
            ->map(fn (string $nama) => Kelas::updateOrCreate(
                ['pesantren_id' => $this->pesantren->id, 'nama_kelas' => $nama],
                ['pesantren_id' => $this->pesantren->id, 'nama_kelas' => $nama],
            ))
            ->all();
    }

    private function seedKamar(): array
    {
        return collect(['Kamar Abu Bakar', 'Kamar Umar', 'Kamar Utsman'])
            ->map(fn (string $nama) => Kamar::updateOrCreate(
                ['pesantren_id' => $this->pesantren->id, 'nama_kamar' => $nama],
                ['pesantren_id' => $this->pesantren->id, 'nama_kamar' => $nama, 'kapasitas' => 8],
            ))
            ->all();
    }

    private function seedUstadz(): array
    {
        return collect([
            ['Ust. Abdurrahman Hakim', 'ustadz1@demo.invalid'],
            ['Ust. Salman Firdaus', 'ustadz2@demo.invalid'],
            ['Ust. Ibrahim Musyaffa', 'ustadz3@demo.invalid'],
        ])->map(fn (array $u) => $this->pastikanUser($u[0], $u[1], 'ustadz'))->all();
    }

    private function seedWali(): array
    {
        return collect([
            ['Bapak Hasan Basri', 'wali1@demo.invalid'],
            ['Bapak Ridwan Kamil', 'wali2@demo.invalid'],
            ['Bapak Sulaiman Yusuf', 'wali3@demo.invalid'],
            ['Ibu Aisyah Rahmawati', 'wali4@demo.invalid'],
            ['Bapak Zainal Abidin', 'wali5@demo.invalid'],
            ['Ibu Khadijah Nurhaliza', 'wali6@demo.invalid'],
        ])->map(fn (array $u) => $this->pastikanUser($u[0], $u[1], 'wali_santri'))->all();
    }

    /**
     * Kata sandi diacak setiap kali dibuat, dan tidak pernah ditampilkan.
     * Kredensial yang bisa ditebak adalah urusan Fase 2 (demo panel admin),
     * bukan sekarang — Fase 1 hanya memublikasikan magic link baca-saja.
     */
    private function pastikanUser(string $nama, string $email, string $role): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'pesantren_id' => $this->pesantren->id,
                'name' => $nama,
                'role' => $role,
                'phone_number' => null,
                'password' => Hash::make(Str::random(40)),
            ]
        );
    }

    private function seedSantri(array $kelas, array $kamar, array $ustadz, array $wali): array
    {
        $nama = [
            'Ahmad Zaki Maulana', 'Fadhil Rabbani Hakim', 'Hilmi Akbar Nugraha',
            'Irfan Maulidi Saputra', 'Naufal Aziz Pratama', 'Rizqi Fadhlan Ramadhan',
            'Yusuf Abdullah Firmansyah', 'Daffa Rasyid Wibowo', 'Faiz Al-Ghazali Nugroho',
            'Haikal Ar-Rasyid Pranata', 'Umar Hafizh Alfarizi', 'Bilal Syauqi Ramadhan',
        ];
        $citaCita = ['Hafidz Quran', 'Ustadz', 'Dokter', 'Pengusaha', 'Guru', 'Insinyur'];

        $out = [];
        foreach ($nama as $i => $n) {
            $out[] = Santri::updateOrCreate(
                [
                    'nis' => 'DEMO-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'pesantren_id' => $this->pesantren->id,
                ],
                [
                    'pesantren_id' => $this->pesantren->id,
                    'wali_santri_id' => $wali[$i % count($wali)]->id,
                    'pembimbing_ustadz_id' => $ustadz[$i % count($ustadz)]->id,
                    'nama_lengkap' => $n,
                    'nama_panggilan' => explode(' ', $n)[0],
                    'tanggal_lahir' => now()->subYears(12 + ($i % 4))->subDays($i * 11),
                    'nama_ayah' => $wali[$i % count($wali)]->name,
                    'nama_ibu' => 'Ibu Contoh '.($i + 1),
                    'alamat_lengkap' => 'Jl. Contoh No. '.($i + 1).', Kota Demo',
                    'jumlah_saudara' => $i % 5,
                    'cita_cita' => $citaCita[$i % count($citaCita)],
                    'kelas_id' => $kelas[$i % count($kelas)]->id,
                    'kamar_id' => $kamar[$i % count($kamar)]->id,
                    'status_aktif' => true,
                ]
            );
        }

        return $out;
    }

    private function seedMataPelajaran(array $kelas, array $ustadz): array
    {
        $out = [];
        foreach ($kelas as $k) {
            foreach (['Fiqih', 'Bahasa Arab', 'Akidah Akhlak'] as $j => $nama) {
                $out[] = MataPelajaran::updateOrCreate(
                    ['pesantren_id' => $this->pesantren->id, 'nama_mapel' => $nama, 'kelas_id' => $k->id],
                    ['ustadz_id' => $ustadz[$j % count($ustadz)]->id],
                );
            }
        }

        return $out;
    }

    private function seedEkskulMaster(): array
    {
        return collect(['Panahan', 'Berkuda', 'Kaligrafi', 'Pramuka', 'Futsal', 'Tahsin'])
            ->map(fn (string $nama) => EkskulMaster::updateOrCreate(
                ['pesantren_id' => $this->pesantren->id, 'nama' => $nama],
                ['aktif' => true],
            ))
            ->all();
    }

    // ─── Transaksional (dihapus & ditulis ulang tiap penyegaran) ──────────

    private function hapusDataTransaksional(array $santri): void
    {
        $ids = collect($santri)->pluck('id')->all();
        $pid = $this->pesantren->id;

        TahfidzProgress::whereIn('santri_id', $ids)->delete();
        TahfidzUjian::whereIn('santri_id', $ids)->delete();
        KesantrianMutabaah::whereIn('santri_id', $ids)->delete();
        Presensi::whereIn('santri_id', $ids)->delete();
        KesantrianKesehatan::whereIn('santri_id', $ids)->delete();
        KesantrianKarakterRapor::whereIn('santri_id', $ids)->delete();
        KesantrianInventaris::whereIn('santri_id', $ids)->delete();
        PrestasiSantri::whereIn('santri_id', $ids)->delete();
        NilaiAkademik::whereIn('santri_id', $ids)->delete();
        SantriEkskul::whereIn('santri_id', $ids)->delete();
        UangSakuSantri::whereIn('santri_id', $ids)->delete();

        PembayaranSpp::whereIn(
            'tagihan_spp_id',
            TagihanSpp::whereIn('santri_id', $ids)->pluck('id')
        )->delete();
        TagihanSpp::whereIn('santri_id', $ids)->delete();

        MasterPengumuman::where('pesantren_id', $pid)->delete();
    }

    private function seedTahfidz(Santri $santri, array $ustadz): void
    {
        $tipe = ['Sabaq', 'Sabqi', 'Manzil'];
        $nilai = ['Mumtaz', 'Jayyid Jiddan', 'Jayyid', 'Maqbul'];

        for ($i = 0; $i < 8; $i++) {
            $mulai = 600 - ($i * 6) - 5;

            TahfidzProgress::create([
                'pesantren_id' => $this->pesantren->id,
                'santri_id' => $santri->id,
                'ustadz_id' => $santri->pembimbing_ustadz_id,
                'tanggal' => now()->subDays($i * 3 + 1),
                'tipe_setoran' => $tipe[$i % count($tipe)],
                'halaman_mulai' => $mulai,
                'halaman_selesai' => $mulai + 5,
                'nama_surah' => null,
                'nilai_kelancaran' => $nilai[$i % count($nilai)],
                'catatan_evaluasi' => 'Bacaan lancar, perhatikan panjang mad.',
            ]);
        }

        TahfidzUjian::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santri->id,
            'penguji_id' => $ustadz[0]->id,
            'tanggal_ujian' => now()->subDays(10),
            'target_juz' => 30,
            'status_kelulusan' => 'Lulus',
            'tahun_ajaran' => TahunAjaranOptions::current(),
            'periode' => 'Semester_Ganjil',
            'nilai_hafalan' => (string) rand(80, 95),
            'nilai_tilawah' => 'A',
            'nilai_makhraj' => 'B',
            'nilai_tajwid' => 'A',
            'rekomendasi_pembimbing' => 'Layak melanjutkan ke juz berikutnya.',
        ]);
    }

    private function seedMutabaah(Santri $santri): void
    {
        for ($i = 0; $i < self::HARI_MUTABAAH; $i++) {
            KesantrianMutabaah::create([
                'pesantren_id' => $this->pesantren->id,
                'santri_id' => $santri->id,
                'tanggal' => now()->subDays($i)->toDateString(),
                'amalan' => [
                    'jamaah_5_waktu' => rand(3, 5),
                    'is_rawatib' => (bool) rand(0, 1),
                    'is_shalat_malam' => (bool) rand(0, 1),
                    'is_dhuha' => (bool) rand(0, 1),
                    'is_tilawah_1juz' => (bool) rand(0, 1),
                    'is_infak' => (bool) rand(0, 1),
                    'is_puasa' => rand(0, 4) === 0,
                ],
                'status_udzur' => 'Tidak',
            ]);
        }
    }

    /**
     * Presensi HARIAN saja (jam_ke = 0). Presensi per jam pelajaran sengaja
     * tidak dibuat: ia tidak pernah ditampilkan ke wali (§v4.40), jadi tidak
     * ada gunanya di demo yang dilihat lewat portal wali.
     */
    private function seedPresensi(Santri $santri): void
    {
        for ($i = 1; $i <= self::HARI_PRESENSI; $i++) {
            $tanggal = now()->subDays($i);

            // Akhir pekan bukan hari efektif — biarkan kosong supaya persentase
            // kehadiran di rapor tidak dihitung dari hari yang tidak berlaku.
            if (in_array($tanggal->dayOfWeek, [0], true)) {
                continue;
            }

            $status = match (true) {
                $i % 17 === 0 => StatusKehadiran::Sakit,
                $i % 13 === 0 => StatusKehadiran::Izin,
                $i % 7 === 0 => StatusKehadiran::Terlambat,
                default => StatusKehadiran::Hadir,
            };

            Presensi::create([
                'pesantren_id' => $this->pesantren->id,
                'santri_id' => $santri->id,
                'tanggal' => $tanggal->toDateString(),
                'jam_ke' => 0,
                'kelas_id' => $santri->kelas_id,
                'status' => $status->value,
                'menit_terlambat' => $status === StatusKehadiran::Terlambat ? rand(5, 20) : null,
                'sumber' => SumberPresensi::Qr->value,
                'dicatat_at' => $tanggal->copy()->setTime(6, rand(45, 59)),
            ]);
        }
    }

    private function seedSpp(Santri $santri): void
    {
        $nominal = 350_000;

        for ($i = 3; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $lunas = $i > 0;

            $tagihan = TagihanSpp::create([
                'pesantren_id' => $this->pesantren->id,
                'santri_id' => $santri->id,
                'bulan' => $bulan->month,
                'tahun' => $bulan->year,
                'nominal' => $nominal,
                'jatuh_tempo' => $bulan->copy()->startOfMonth()->addDays(9),
                'keterangan' => 'SPP Bulanan',
                'status' => $lunas ? 'lunas' : 'belum_bayar',
            ]);

            if ($lunas) {
                PembayaranSpp::create([
                    'pesantren_id' => $this->pesantren->id,
                    'tagihan_spp_id' => $tagihan->id,
                    'jumlah' => $nominal,
                    'tanggal_bayar' => $bulan->copy()->startOfMonth()->addDays(5),
                    'metode_bayar' => 'transfer_bank',
                    'dicatat_oleh' => null,
                ]);
            }
        }
    }

    private function seedNilaiAkademik(Santri $santri, array $mapel): void
    {
        $milik = collect($mapel)->where('kelas_id', $santri->kelas_id)->values();

        foreach ($milik as $m) {
            NilaiAkademik::create([
                'pesantren_id' => $this->pesantren->id,
                'santri_id' => $santri->id,
                'mata_pelajaran_id' => $m->id,
                'nilai' => rand(75, 96),
                'tahun_ajaran' => TahunAjaranOptions::current(),
                'periode' => TahunAjaranOptions::currentPeriode(),
                'catatan' => null,
            ]);
        }
    }

    private function seedKesehatan(Santri $santri): void
    {
        KesantrianKesehatan::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santri->id,
            'tanggal_periksa' => now()->subDays(rand(5, 25)),
            'berat_badan' => rand(30, 55),
            'tinggi_badan' => rand(130, 165),
            'kategori_keluhan' => 'Demam',
            'detail_keluhan_teks' => 'Mengeluh demam ringan dan batuk sejak semalam.',
            'tindakan_dan_obat' => 'Istirahat di UKS, diberi obat penurun panas.',
            'status_pemulihan' => 'Rawat_Mandiri',
        ]);
    }

    private function seedKarakterRapor(Santri $santri): void
    {
        $grade = ['A', 'B', 'B', 'A'];
        $pick = fn () => $grade[array_rand($grade)];

        KesantrianKarakterRapor::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santri->id,
            'tahun_ajaran' => TahunAjaranOptions::current(),
            'periode' => TahunAjaranOptions::currentPeriode(),
            'tanggal_input' => now()->subDays(rand(1, 15)),
            'adab_ustadz' => $pick(),
            'adab_tamu' => $pick(),
            'adab_asrama' => $pick(),
            'adab_kelas' => $pick(),
            'adab_sholat' => $pick(),
            'adab_quran' => $pick(),
            'adab_minum' => $pick(),
            'kepribadian_tanggungjawab' => $pick(),
            'kepribadian_kemandirian' => $pick(),
            'kepribadian_kepatuhan' => $pick(),
            'kepribadian_kebersihan' => $pick(),
            'kepribadian_mengelola' => $pick(),
            'kepribadian_kepedulian' => $pick(),
            'kepribadian_empati' => $pick(),
            'kepribadian_kebersamaan' => $pick(),
            'kepribadian_kedisiplinan' => $pick(),
            'log_kasus_khusus' => null,
        ]);
    }

    private function seedInventaris(Santri $santri): void
    {
        foreach (['Sarung', 'Sajadah'] as $j => $nama) {
            KesantrianInventaris::create([
                'pesantren_id' => $this->pesantren->id,
                'santri_id' => $santri->id,
                'nama_barang_umum' => $nama,
                'kode_unik_fisik' => 'DEMO-'.$santri->id.'-'.($j + 1),
                'kuota_regulasi_maksimal' => 2,
                'kondisi_barang' => 'Baik',
                'tanggal_sidak_terakhir' => now()->subDays(rand(3, 25)),
            ]);
        }
    }

    private function seedUangSaku(Santri $santri): void
    {
        UangSakuSantri::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santri->id,
            'jenis' => JenisUangSaku::Setoran->value,
            'nominal' => 500_000,
            'tanggal' => now()->subDays(28),
            'keterangan' => 'Setoran awal bulan dari wali',
            'dicatat_oleh' => null,
        ]);

        foreach ([18, 11, 4] as $hari) {
            UangSakuSantri::create([
                'pesantren_id' => $this->pesantren->id,
                'santri_id' => $santri->id,
                'jenis' => JenisUangSaku::Pengambilan->value,
                'nominal' => rand(5, 12) * 10_000,
                'tanggal' => now()->subDays($hari),
                'keterangan' => 'Pengambilan untuk keperluan harian',
                'dicatat_oleh' => null,
            ]);
        }
    }

    private function seedPrestasi(Santri $santri): void
    {
        PrestasiSantri::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santri->id,
            'judul' => 'Juara Musabaqah Hifzhil Quran',
            'kategori' => array_key_first(PrestasiSantri::$kategoriOptions),
            'tingkat' => 'kabupaten',
            'posisi' => array_key_first(PrestasiSantri::$posisiOptions),
            'tanggal' => now()->subMonths(rand(1, 5)),
            'penyelenggara' => 'Kemenag Kabupaten/Kota',
            'keterangan' => null,
        ]);
    }

    private function seedSantriEkskul(Santri $santri, array $ekskul): void
    {
        $level = ['pemula', 'menengah', 'mahir'];

        foreach ([0, 1] as $offset) {
            $e = $ekskul[($santri->id + $offset) % count($ekskul)];

            SantriEkskul::firstOrCreate(
                ['santri_id' => $santri->id, 'ekskul_id' => $e->id],
                [
                    'pesantren_id' => $this->pesantren->id,
                    'level' => $level[($santri->id + $offset) % count($level)],
                    'tanggal_mulai' => now()->subMonths(rand(2, 9)),
                    'aktif' => true,
                ]
            );
        }
    }

    private function seedPengumuman(): void
    {
        $items = [
            ['Jadwal Ujian Tahfidz Semester Ini', 'Ujian tahfidz akan dilaksanakan dua pekan lagi. Mohon dukungan wali agar ananda memperbanyak murajaah.', 'semua'],
            ['Pembayaran SPP Bulan Ini', 'Tagihan SPP sudah terbit di portal wali. Konfirmasi transfer bisa langsung dilakukan dari halaman SPP.', 'wali'],
            ['Kunjungan Wali Santri', 'Kunjungan wali dibuka setiap akhir pekan pukul 09.00-15.00.', 'wali'],
        ];

        foreach ($items as [$judul, $isi, $target]) {
            MasterPengumuman::create([
                'pesantren_id' => $this->pesantren->id,
                'judul_maklumat' => $judul,
                'isi_maklumat' => $isi,
                'target_audience' => $target,
            ]);
        }
    }
}
