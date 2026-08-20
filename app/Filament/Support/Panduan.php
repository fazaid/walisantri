<?php

namespace App\Filament\Support;

use App\Filament\Clusters;
use App\Filament\Pages;
use App\Filament\Resources;

/**
 * Naskah panduan singkat per menu, beserta resolusi halaman-yang-sedang-dibuka.
 *
 * Dipakai oleh satu render hook PAGE_HEADER_ACTIONS_BEFORE (AdminPanelProvider)
 * yang memasang tombol "Panduan" di baris judul SETIAP halaman panel. Modalnya
 * menjawab "menu ini buat apa, dan langkah pakainya bagaimana"; jawaban panjang
 * tetap tinggal di halaman /panduan, ditautkan lewat field 'anchor'.
 *
 * Tiga hal yang menentukan bentuk berkas ini:
 *
 * 1. SATU ARRAY, BUKAN ~45 PARTIAL BLADE. Tiap entri cuma 3-6 baris teks, jadi
 *    memecahnya jadi berkas sendiri-sendiri hanya menyebar naskah tanpa imbalan.
 *    Dikumpulkan di sini, seluruh nada panduan bisa dibaca dan disamakan dalam
 *    satu layar — dan kelengkapannya bisa ditegakkan dari luar oleh test.
 *
 * 2. KUNCINYA RESOURCE, BUKAN HALAMAN. Sub-halaman sebuah resource (List/View)
 *    memakai entri resource induknya, karena "menu" yang dikenal pengguna adalah
 *    resource-nya. Proyek ini tidak punya Create/Edit page — semua lewat modal —
 *    jadi yang perlu diresolusi memang hanya List* dan View*.
 *
 * 3. PERBEDAAN ROLE CUKUP SATU KALIMAT. Field 'ustadz' dirender hanya untuk
 *    ustadz. Menggandakan seluruh entri per role ditolak: bedanya selalu berupa
 *    batasan tunggal ("hanya santri bimbingan Anda"), bukan langkah yang berbeda.
 *
 * Menambah menu baru = menambah satu entri di sini. Tidak ada berkas lain yang
 * perlu disentuh, dan TombolPanduanTest akan menagih entrinya bila lupa.
 *
 * Bentuk entri:
 *   'judul'   => judul modal (string, wajib)
 *   'ringkas' => satu kalimat "ini menu apa" (string, wajib)
 *   'langkah' => langkah berurut, boleh mengandung <strong>/<code> (array, wajib)
 *   'catatan' => satu peringatan/tip yang paling sering bikin salah (opsional)
 *   'ustadz'  => batasan khusus role ustadz, dirender hanya untuknya (opsional)
 *   'anchor'  => id seksi di resources/views/panduan.blade.php (opsional)
 */
class Panduan
{
    /**
     * @var array<class-string, array{judul: string, ringkas: string, langkah: array<string>, catatan?: string, ustadz?: string, anchor?: string}>
     */
    public const PETA = [
        // ---------------------------------------------------------------- Umum
        Pages\Dashboard::class => [
            'judul' => 'Dasbor',
            'ringkas' => 'Ringkasan keadaan pesantren hari ini, berbeda isi untuk tiap peran.',
            'langkah' => [
                'Baca kartu statistik paling atas — admin melihat jumlah santri aktif vs kuota paket, jumlah ustadz &amp; wali, santri sakit hari ini, dan status langganan.',
                'Ustadz melihat kartu yang berbeda: jumlah santri binaan, setoran yang sudah dicatat hari ini, dan santri binaan yang <strong>belum</strong> diisi mutaba\'ah.',
                'Klik kartu <strong>status langganan</strong> untuk melompat ke halaman Langganan.',
                'Di bawahnya ada grafik tren dan pengumuman terbaru.',
            ],
            'ustadz' => 'Kartu "belum diisi mutaba\'ah hari ini" adalah pengingat harian Anda — angkanya harus nol di akhir hari.',
            'anchor' => 'dashboard',
        ],

        // ------------------------------------------------------- Cluster Santri
        Resources\Santris\SantriResource::class => [
            'judul' => 'Data Santri',
            'ringkas' => 'Daftar induk seluruh santri — sumber data untuk hampir semua menu lain.',
            'langkah' => [
                'Buat dulu daftar <strong>Kelas</strong> dan <strong>Kamar</strong>, supaya keduanya bisa langsung dipilih di form.',
                'Klik <strong>+ New</strong>, lalu isi <strong>NIS</strong> (wajib unik), nama, tanggal lahir, jenis kelamin, kelas, dan kamar.',
                'Di bagian <strong>Relasi</strong>, hubungkan santri ke akun Wali Santri dan Ustadz Pembimbing.',
                'Simpan. Santri langsung tersedia di menu Tahfidz, Presensi, Kesantrian, dan Keuangan.',
                'Pakai aksi baris <strong>Link Wali</strong> untuk menyalin tautan portal, atau <strong>Preview sebagai Wali</strong> untuk memeriksa tampilannya.',
                'Untuk memindahkan banyak santri sekaligus, centang barisnya lalu pilih <strong>Pindah Kelas</strong> atau <strong>Pindah Kamar</strong>.',
            ],
            'catatan' => 'Tanpa Wali Santri terhubung, Link Portal Wali tidak bisa dibuat. Satu ustadz maksimal membimbing 20 santri aktif.',
            'ustadz' => 'Anda melihat semua santri, tetapi hanya bisa mengubah santri bimbingan Anda sendiri dan tidak bisa menghapus.',
            'anchor' => 'manajemen-santri',
        ],

        Resources\Kelas\KelasResource::class => [
            'judul' => 'Kelas',
            'ringkas' => 'Data master kelas — isi lebih dulu sebelum menambahkan santri.',
            'langkah' => [
                'Klik <strong>+ New</strong>, isi <strong>Nama Kelas</strong>.',
                'Simpan. Kelas langsung bisa dipilih di form Santri, Mata Pelajaran, dan Tarif SPP.',
            ],
            'catatan' => 'Nama kelas harus unik dalam satu pesantren.',
            'anchor' => 'manajemen-santri',
        ],

        Resources\Kamars\KamarResource::class => [
            'judul' => 'Kamar',
            'ringkas' => 'Data master kamar asrama beserta kapasitasnya.',
            'langkah' => [
                'Klik <strong>+ New</strong>, isi <strong>Nama Kamar</strong> dan <strong>Kapasitas</strong>.',
                'Isi kapasitas <code>0</code> bila kamar itu tanpa batas.',
                'Simpan. Kamar langsung bisa dipilih di form Santri.',
            ],
            'anchor' => 'manajemen-santri',
        ],

        Resources\PrestasiSantris\PrestasiSantriResource::class => [
            'judul' => 'Prestasi Santri',
            'ringkas' => 'Catatan pencapaian lomba dan kejuaraan santri.',
            'langkah' => [
                'Klik <strong>+ New</strong>, pilih Santri dan Tanggal Prestasi.',
                'Isi judul lomba, kategori, tingkat (internal sampai internasional), peringkat, dan penyelenggara.',
                'Unggah sertifikat atau foto piala bila ada (JPG/PNG/PDF, maks 5 MB).',
                'Simpan — prestasi otomatis muncul di portal wali santri terkait.',
            ],
            'ustadz' => 'Anda bisa menambah dan mengubah prestasi, tetapi tidak bisa menghapusnya.',
            'anchor' => 'manajemen-santri',
        ],

        // ------------------------------------------------------ Cluster Tahfidz
        Resources\TahfidzProgress\TahfidzProgressResource::class => [
            'judul' => 'Setoran Tahfidz',
            'ringkas' => 'Catatan hafalan harian santri, satu baris per setoran.',
            'langkah' => [
                'Klik <strong>+ New</strong>, pilih Santri dan Ustadz Pencatat (terisi otomatis bila Anda ustadz).',
                'Pilih tanggal dan <strong>Tipe Setoran</strong>: Sabaq (hafalan baru), Sabqi (hafalan kemarin), atau Manzil (hafalan lama).',
                'Isi <strong>Halaman Mulai</strong> dan <strong>Halaman Selesai</strong> — capaian juz dihitung dari halaman mushaf.',
                'Isi <strong>Nilai Kelancaran</strong>: Mumtaz, Jayyid Jiddan, Jayyid, atau Maqbul, plus catatan evaluasi bila perlu.',
                'Simpan.',
            ],
            'ustadz' => 'Anda hanya bisa mencatat dan mengubah setoran santri bimbingan Anda, dan tidak bisa menghapus.',
            'anchor' => 'tahfidz',
        ],

        Resources\TahfidzUjian\TahfidzUjianResource::class => [
            'judul' => 'Ujian Tahfidz',
            'ringkas' => 'Hasil ujian kenaikan juz, yang nantinya tampil di rapor tahfidz.',
            'langkah' => [
                'Klik <strong>+ New</strong>, pilih Santri, Penguji, Tanggal Ujian, Target Juz, dan status Lulus/Mengulang.',
                'Isi <strong>Periode Rapor</strong> — tahun ajaran dan periode inilah yang menentukan rapor mana yang menampilkan hasil ini.',
                'Isi penilaian: Nilai Hafalan serta Tilawah, Makhraj, dan Tajwid (skala A–D).',
                'Isi <strong>Rekomendasi Pembimbing</strong> — wajib.',
                'Simpan.',
            ],
            'anchor' => 'tahfidz',
        ],

        // ----------------------------------------------------- Cluster Akademik
        Resources\MataPelajarans\MataPelajaranResource::class => [
            'judul' => 'Mata Pelajaran',
            'ringkas' => 'Daftar mata pelajaran per kelas beserta ustadz pengampunya.',
            'langkah' => [
                'Klik <strong>+ New</strong>, pilih Kelas dan Ustadz Pengampu, lalu isi nama mata pelajaran.',
                'Simpan.',
            ],
            'catatan' => 'Ustadz hanya bisa menginput nilai untuk mata pelajaran yang ia ampu — isian pengampu di sini yang menentukannya.',
            'anchor' => 'akademik',
        ],

        Resources\NilaiAkademiks\NilaiAkademikResource::class => [
            'judul' => 'Nilai Akademik',
            'ringkas' => 'Nilai per santri per mata pelajaran, bahan hitung rapor akademik.',
            'langkah' => [
                'Klik <strong>+ New</strong>, pilih <strong>Mata Pelajaran</strong> lebih dulu — daftar santri menyesuaikan kelas mapel itu.',
                'Pilih Santri, Tahun Ajaran, Periode, dan Bulan bila periodenya Bulanan.',
                'Isi <strong>Nilai</strong> (0–100) dan catatan bila perlu.',
                'Simpan — rapor akademik dihitung otomatis dari kumpulan nilai ini.',
            ],
            'catatan' => 'Untuk mengisi satu kelas sekaligus, pakai halaman Input Nilai Massal lewat tombol di header daftar ini.',
            'ustadz' => 'Anda hanya bisa mengisi nilai untuk mata pelajaran yang Anda ampu.',
            'anchor' => 'akademik',
        ],

        Pages\NilaiMassalPage::class => [
            'judul' => 'Input Nilai Massal',
            'ringkas' => 'Mengisi nilai satu mata pelajaran untuk seluruh santri sekaligus.',
            'langkah' => [
                'Pilih <strong>Mata Pelajaran</strong>, Tahun Ajaran, Periode, dan Bulan bila Bulanan.',
                'Daftar santri kelas itu muncul otomatis; nilai yang sudah pernah diisi ikut tampil dan bisa diubah.',
                'Isi <strong>Nilai (0-100)</strong> dan catatan untuk tiap santri.',
                'Klik <strong>Simpan Semua</strong> — seluruh baris tersimpan sekaligus.',
            ],
            'anchor' => 'akademik',
        ],

        Resources\EkskulMasters\EkskulMasterResource::class => [
            'judul' => 'Ekstrakurikuler',
            'ringkas' => 'Daftar master ekskul yang tersedia di pesantren.',
            'langkah' => [
                'Klik <strong>+ New</strong>, isi nama ekskul, nama pembina, dan deskripsi.',
                'Atur status <strong>Aktif</strong> — ekskul nonaktif tidak lagi bisa dipilih, tetapi riwayat lamanya tetap tersimpan.',
                'Simpan.',
            ],
            'anchor' => 'akademik',
        ],

        Resources\SantriEkskuls\SantriEkskulResource::class => [
            'judul' => 'Ekskul Santri',
            'ringkas' => 'Catatan santri mana mengikuti ekskul apa.',
            'langkah' => [
                'Daftarkan dulu ekskulnya di menu Ekskul.',
                'Klik <strong>+ New</strong>, pilih Santri dan Ekskul.',
                'Pilih <strong>Level</strong> (Pemula, Menengah, Mahir) dan isi tanggal mulai.',
                'Simpan — ekskul aktif ikut tampil di rapor akademik.',
            ],
            'catatan' => 'Untuk menghentikan partisipasi tanpa menghapus riwayat, matikan toggle Aktif alih-alih menghapus barisnya.',
            'anchor' => 'akademik',
        ],

        // ----------------------------------------------------- Cluster Presensi
        Resources\Presensis\PresensiResource::class => [
            'judul' => 'Kehadiran',
            'ringkas' => 'Rekaman seluruh presensi santri, dari semua cara pengisian.',
            'langkah' => [
                'Halaman ini untuk <strong>menelusuri dan mengoreksi</strong>; pengisiannya lewat tombol di header.',
                'Klik <strong>Isi Presensi</strong> untuk satu kelas satu hari, <strong>Isi per Jam</strong> untuk per jam pelajaran, atau <strong>Scan QR</strong> untuk memakai kartu.',
                'Pakai filter Status, Jenis, Santri, dan Kelas untuk mempersempit daftar.',
                'Kolom <strong>Sumber</strong> menunjukkan asal data — pengisian manual, scan kartu, atau izin yang disetujui.',
            ],
        ],

        Pages\PresensiHarianPage::class => [
            'judul' => 'Isi Presensi Harian',
            'ringkas' => 'Mencatat kehadiran satu kelas untuk satu tanggal, dalam satu layar.',
            'langkah' => [
                'Pilih <strong>Tanggal</strong>, lalu <strong>Kelompok</strong>: per kelas, semua santri, atau santri tanpa kelas.',
                'Semua santri muncul berstatus <strong>Hadir</strong>. Ubah hanya yang menyimpang.',
                'Isi <strong>Catatan</strong> untuk santri yang perlu penjelasan.',
                'Klik <strong>Simpan Presensi</strong>.',
            ],
            'catatan' => 'Mengisi ulang tanggal yang sama memperbarui isian sebelumnya, bukan menggandakannya. Izin yang sudah disetujui ikut terisi sendiri.',
        ],

        Pages\PresensiJamPage::class => [
            'judul' => 'Isi Presensi per Jam Pelajaran',
            'ringkas' => 'Presensi yang dicatat per mata pelajaran, bukan sekali sehari.',
            'langkah' => [
                'Aktifkan dulu presensi per jam di <strong>Pengaturan Presensi</strong>, lalu daftarkan jam pelajarannya lewat <strong>Atur Jam Pelajaran</strong>.',
                'Pilih <strong>Tanggal</strong>, <strong>Mata Pelajaran</strong>, dan <strong>Jam ke-</strong>.',
                'Ubah status santri yang tidak hadir, isi catatan bila perlu.',
                'Klik <strong>Simpan Presensi</strong>.',
            ],
        ],

        Pages\PresensiScannerPage::class => [
            'judul' => 'Scan Kartu Presensi',
            'ringkas' => 'Mencatat kehadiran dengan memindai QR pada kartu santri.',
            'langkah' => [
                'Cetak dulu kartu presensi santri, yang memuat QR berisi kode unik masing-masing.',
                'Pakai alat pemindai QR, atau klik <strong>Pindai dengan Kamera</strong> bila pesantren tidak punya alatnya.',
                'Arahkan QR kartu ke kamera. Jauhkan kartunya lebih dulu sebelum mendekatkan kartu berikutnya — tiap kartu dicatat sekali.',
                'Santri yang sudah terpindai hari itu akan diberi tahu statusnya, bukan dicatat dua kali.',
            ],
            'catatan' => 'Kalau kartu hilang atau bocor, buat ulang kodenya lewat aksi Ganti Kode Kartu Presensi di daftar Santri — kartu lama langsung tidak berlaku.',
        ],

        Pages\PresensiRekapPage::class => [
            'judul' => 'Rekap Kehadiran',
            'ringkas' => 'Rekapitulasi kehadiran per santri untuk rentang tanggal tertentu.',
            'langkah' => [
                'Pilih rentang tanggal dan penyaring yang diinginkan.',
                'Tabel menampilkan jumlah hadir, sakit, izin, dan alpa per santri.',
                'Klik <strong>Ekspor Excel</strong> untuk mengunduh rekapnya.',
            ],
        ],

        Resources\PresensiIzins\PresensiIzinResource::class => [
            'judul' => 'Pengajuan Izin',
            'ringkas' => 'Permohonan izin santri, baik dari wali maupun yang dicatat pengurus.',
            'langkah' => [
                'Klik <strong>+ New</strong> untuk mencatat izin sendiri, atau tunggu pengajuan masuk dari portal wali.',
                'Isi Santri, <strong>Jenis Izin</strong>, tanggal mulai dan selesai, serta alasannya.',
                'Untuk pengajuan yang masuk, klik <strong>Setujui</strong> atau <strong>Tolak</strong> pada barisnya.',
                'Izin yang disetujui otomatis mengisi presensi santri pada tanggal-tanggal tersebut.',
            ],
            'catatan' => 'Kolom Asal menunjukkan apakah izin diajukan wali lewat portal atau dicatat pengurus langsung.',
        ],

        Resources\PresensiHariLiburs\PresensiHariLiburResource::class => [
            'judul' => 'Hari Libur',
            'ringkas' => 'Tanggal yang dikecualikan dari perhitungan kehadiran.',
            'langkah' => [
                'Klik <strong>+ New</strong>, isi tanggal, keterangan, dan tahun ajaran.',
                'Untuk libur beberapa hari berturut-turut, pakai isian <strong>Tanggal Mulai</strong> dan <strong>Tanggal Selesai</strong>.',
                'Simpan — tanggal itu tidak lagi dihitung sebagai hari efektif di rekap kehadiran.',
            ],
        ],

        Resources\PresensiJamPelajarans\PresensiJamPelajaranResource::class => [
            'judul' => 'Jam Pelajaran',
            'ringkas' => 'Daftar jam pelajaran yang dipakai presensi per jam.',
            'langkah' => [
                'Klik <strong>+ New</strong>, isi urutan jam beserta waktu mulai dan selesainya.',
                'Simpan — jam ini muncul sebagai pilihan di halaman Isi Presensi per Jam Pelajaran.',
            ],
            'catatan' => 'Halaman ini hanya berguna bila presensi per jam sudah diaktifkan di Pengaturan Presensi.',
        ],

        Pages\PresensiPengaturanPage::class => [
            'judul' => 'Pengaturan Presensi',
            'ringkas' => 'Aturan main presensi: jam masuk, toleransi, dan siapa boleh mengubah apa.',
            'langkah' => [
                'Isi <strong>Jam Masuk</strong> dan <strong>Toleransi Terlambat (menit)</strong> — lewat batas itu santri tercatat Terlambat.',
                'Aktifkan <strong>presensi per jam pelajaran</strong> bila pesantren mencatat kehadiran tiap mata pelajaran.',
                'Isi <strong>Batas Edit Ustadz (hari)</strong> untuk membatasi berapa lama ke belakang ustadz boleh mengoreksi presensi.',
                'Klik <strong>Simpan Pengaturan</strong>.',
            ],
        ],

        // --------------------------------------------------- Cluster Kesantrian
        Resources\KesantrianAmalMasters\KesantrianAmalMasterResource::class => [
            'judul' => 'Amal Master',
            'ringkas' => 'Menentukan amalan apa saja yang dicatat setiap hari.',
            'langkah' => [
                'Klik <strong>+ New</strong>, isi Nama Amal dan ikon emoji bila mau.',
                'Pilih <strong>Tipe Penilaian</strong>: Centang untuk amalan ya/tidak, atau Hitungan untuk yang dihitung — isi juga nilai maksimalnya.',
                'Isi satuan, <strong>Bobot Poin</strong> (kontribusi ke skor harian), dan urutan tampil.',
                'Simpan.',
            ],
            'catatan' => 'Amal yang dinonaktifkan hilang dari form Isi Harian, tetapi riwayat lamanya tetap tersimpan.',
            'anchor' => 'mutabaah',
        ],

        Pages\MutabaahHarianPage::class => [
            'judul' => 'Isi Mutaba\'ah Harian',
            'ringkas' => 'Cara tercepat mengisi mutaba\'ah banyak santri sekaligus untuk satu hari.',
            'langkah' => [
                'Daftarkan dulu amalannya di menu <strong>Amal Master</strong>.',
                'Pilih <strong>Tanggal</strong> — daftar santri beserta form amalannya muncul, lengkap dengan data yang sudah pernah diisi.',
                'Isi kolom <strong>Udzur</strong> (Tidak, Sakit, Haid, Izin Pulang, Tugas Pondok) dan nilai tiap amalan.',
                'Klik <strong>Simpan Semua</strong>.',
            ],
            'catatan' => 'Udzur "Sakit" tersinkron otomatis dengan rekam kesehatan bertipe Istirahat Total atau Rujukan Luar pada tanggal yang sama.',
            'ustadz' => 'Daftar yang muncul hanya santri bimbingan Anda.',
            'anchor' => 'mutabaah',
        ],

        Resources\KesantrianMutabaahs\KesantrianMutabaahResource::class => [
            'judul' => 'Mutaba\'ah',
            'ringkas' => 'Log amalan harian per santri — dipakai untuk koreksi satu-satu.',
            'langkah' => [
                'Untuk pengisian harian banyak santri, pakai halaman <strong>Isi Harian</strong>; halaman ini untuk memperbaiki satu entri.',
                'Klik <strong>+ New</strong> atau ikon pensil pada baris yang ingin dikoreksi.',
                'Pilih Santri dan tanggal, isi udzur serta nilai tiap amalan.',
                'Simpan.',
            ],
            'anchor' => 'mutabaah',
        ],

        Resources\KesantrianKarakterRapors\KesantrianKarakterRaporResource::class => [
            'judul' => 'Rapor Karakter',
            'ringkas' => 'Penilaian adab dan kepribadian santri per periode.',
            'langkah' => [
                'Klik <strong>+ New</strong>, pilih Santri, Tahun Ajaran, Periode, Bulan bila Bulanan, dan tanggal input.',
                'Isi <strong>Penilaian Adab</strong> (skala A–D): ke ustadz, ke tamu, asrama, kelas, sholat, Al-Quran, dan minum.',
                'Isi <strong>Penilaian Kepribadian</strong> (skala A–D): tanggung jawab, kemandirian, kepatuhan, kebersihan, dan seterusnya.',
                'Isi <strong>Log Kasus Khusus</strong> bila ada catatan penting.',
                'Simpan.',
            ],
            'catatan' => 'Sistem menolak entri ganda untuk kombinasi santri dan periode yang sama.',
            'anchor' => 'kesantrian',
        ],

        Resources\KesantrianKesehatans\KesantrianKesehatanResource::class => [
            'judul' => 'Kesehatan',
            'ringkas' => 'Rekam medis santri — keluhan sakit maupun pemeriksaan rutin.',
            'langkah' => [
                'Klik <strong>+ New</strong>, pilih Santri dan Tanggal Periksa.',
                'Pilih <strong>Jenis Rekam</strong>: Keluhan Sakit atau Pemeriksaan Rutin — untuk Rutin, bagian keluhan disembunyikan sendiri.',
                'Untuk Keluhan Sakit, isi kategori, detail keluhan, tindakan &amp; obat, dan <strong>Status Pemulihan</strong>.',
                'Bila statusnya Sembuh, isi juga tanggal sembuhnya.',
                'Simpan.',
            ],
            'catatan' => 'Status Istirahat Total atau Rujukan Luar otomatis memicu udzur "Sakit" di mutaba\'ah tanggal yang sama, dan santri muncul di kartu Santri Sakit pada Dasbor.',
            'anchor' => 'kesantrian',
        ],

        Resources\KesantrianInventaris\KesantrianInventarisResource::class => [
            'judul' => 'Inventaris Santri',
            'ringkas' => 'Barang milik santri yang dititipkan di pesantren.',
            'langkah' => [
                'Klik <strong>+ New</strong>, pilih Santri dan isi nama barang.',
                'Isi <strong>Kode Unik Fisik</strong> (harus unik, misal <code>FZ-SRG-01</code>) dan kuota maksimalnya.',
                'Pilih <strong>Kondisi</strong>: Baik, Layak Pakai, atau Hilang, serta tanggal sidak terakhir bila ada.',
                'Simpan.',
            ],
            'anchor' => 'kesantrian',
        ],

        // ------------------------------------------------------------- Rapor
        Pages\RaporPage::class => [
            'judul' => 'Rapor Santri',
            'ringkas' => 'Rekap per santri per periode, dihitung otomatis dari data yang sudah diinput.',
            'langkah' => [
                'Pilih <strong>Santri</strong>, lalu Tahun Ajaran dan Periode — pilih Bulan bila periodenya Bulanan.',
                'Buka tab yang diinginkan: Akademik, Tahfidz, Mutaba\'ah, atau Karakter.',
                'Data tampil otomatis; tidak ada yang perlu diisi di halaman ini.',
                'Klik <strong>Unduh PDF</strong> untuk mencetaknya.',
            ],
            'catatan' => 'Rapor kosong berarti data sumbernya memang belum ada — isi dulu di menu modul yang bersangkutan.',
            'ustadz' => 'Daftar santri di dropdown hanya berisi santri bimbingan Anda.',
            'anchor' => 'rapor',
        ],

        // ----------------------------------------------------- Cluster Keuangan
        Resources\TarifSpps\TarifSppResource::class => [
            'judul' => 'Tarif SPP',
            'ringkas' => 'Nominal SPP bulanan per kelas, jadi acuan saat menerbitkan tagihan.',
            'langkah' => [
                'Klik <strong>+ New</strong>, pilih Kelas dan isi Nominal SPP.',
                'Simpan — nominal ini yang dipakai saat generate tagihan massal.',
            ],
            'catatan' => 'Kelas yang belum punya tarif akan dilewati saat generate tagihan, dan jumlahnya dilaporkan di notifikasi.',
            'anchor' => 'keuangan',
        ],

        Resources\TagihanSpps\TagihanSppResource::class => [
            'judul' => 'Tagihan SPP',
            'ringkas' => 'Tagihan bulanan santri beserta status pembayarannya.',
            'langkah' => [
                'Pastikan <strong>Tarif SPP</strong> tiap kelas sudah diisi.',
                'Klik <strong>Generate Tagihan Massal</strong>, pilih bulan dan tahun, isi jatuh tempo dan keterangan.',
                'Proses — tagihan dibuat untuk semua santri aktif sesuai tarif kelasnya masing-masing.',
                'Untuk menerima pembayaran, klik <strong>Tandai Lunas</strong> pada barisnya lalu isi tanggal dan metode bayar.',
                'Tagihan bertanda <strong>!</strong> berarti wali sudah mengunggah bukti transfer — periksa buktinya di halaman detail sebelum melunaskan.',
                'Bila bukti tidak sah, klik <strong>Tolak</strong>: status kembali Belum Bayar dan buktinya dihapus.',
            ],
            'catatan' => 'Santri yang tagihan bulan itu sudah ada tidak akan digandakan saat generate ulang.',
            'anchor' => 'keuangan',
        ],

        Resources\UangSakus\UangSakuResource::class => [
            'judul' => 'Uang Saku',
            'ringkas' => 'Buku besar setoran dan pengambilan uang saku santri.',
            'langkah' => [
                'Klik <strong>+ New</strong>, pilih Santri dan <strong>Jenis Transaksi</strong> (Setoran atau Pengambilan).',
                'Isi nominal, tanggal, dan keterangan bila perlu.',
                'Simpan — saldo santri terhitung ulang otomatis.',
            ],
            'catatan' => 'Untuk melihat saldo seluruh santri sekaligus, buka menu Saldo Uang Saku Santri.',
            'anchor' => 'keuangan',
        ],

        Pages\SaldoUangSakuPage::class => [
            'judul' => 'Saldo Uang Saku Santri',
            'ringkas' => 'Saldo berjalan tiap santri, dihitung dari setoran dikurangi pengambilan.',
            'langkah' => [
                'Cari santri lewat kolom pencarian.',
                'Saldo terhitung otomatis dari transaksi di menu Uang Saku — tidak ada yang perlu diisi di sini.',
                'Untuk menambah atau mengurangi saldo, catat transaksinya di menu <strong>Uang Saku</strong>.',
            ],
            'anchor' => 'keuangan',
        ],

        // -------------------------------------------------------- Grup Manajemen
        Resources\Users\UserResource::class => [
            'judul' => 'Pengguna',
            'ringkas' => 'Akun ustadz, wali santri, dan admin di pesantren Anda.',
            'langkah' => [
                'Klik <strong>+ New</strong>, isi nama, email, dan nomor telepon.',
                'Pilih <strong>Role</strong>: Admin Pesantren, Ustadz, atau Wali Santri.',
                'Isi password minimal 8 karakter beserta konfirmasinya, lalu simpan.',
                'Untuk ustadz baru, hubungkan santri bimbingannya lewat Edit Santri → bagian Relasi.',
            ],
            'catatan' => 'Email boleh dikosongkan khusus Wali Santri yang hanya punya nomor WhatsApp — magic link tetap berfungsi tanpa email.',
            'anchor' => 'manajemen-pengguna',
        ],

        Resources\MasterPengumumen\MasterPengumumanResource::class => [
            'judul' => 'Pengumuman',
            'ringkas' => 'Maklumat yang tampil di dasbor pengurus dan portal wali santri.',
            'langkah' => [
                'Klik <strong>+ New</strong>, isi judul dan isi pengumuman lewat editor teks kaya.',
                'Pilih <strong>Tampilkan Kepada</strong>: semua pengguna, admin &amp; ustadz saja, atau wali santri saja.',
                'Simpan — pengumuman langsung tampil ke audiens yang dipilih.',
            ],
            'ustadz' => 'Anda hanya bisa membaca pengumuman, tidak membuat atau mengubahnya.',
            'anchor' => 'pengumuman',
        ],

        Pages\PesantrenSettingsPage::class => [
            'judul' => 'Pengaturan Pesantren',
            'ringkas' => 'Identitas dan profil publik pesantren yang tampil di halaman profil.',
            'langkah' => [
                'Isi <strong>Identitas Pesantren</strong>: nama dan subdomain.',
                'Unggah logo (PNG/JPG/SVG, maks 1 MB) dan foto galeri (maks 12, urutannya bisa digeser).',
                'Lengkapi profil publik: telepon, alamat, deskripsi, program &amp; jenjang, tahun berdiri, dan akreditasi.',
                'Tambahkan <strong>Rekening Pembayaran SPP</strong> — daftar inilah yang dilihat wali saat membuka tagihan.',
                'Klik <strong>Simpan Perubahan</strong>.',
            ],
            'catatan' => 'Mengubah subdomain melepas alamat lama ke masa tunggu 90 hari sebelum bisa dipakai pesantren lain.',
            'anchor' => 'pengaturan',
        ],

        Pages\ModulPengaturanPage::class => [
            'judul' => 'Pengaturan Modul',
            'ringkas' => 'Tuas untuk menyembunyikan modul yang tidak dipakai pesantren Anda.',
            'langkah' => [
                'Matikan modul yang tidak dipakai — menunya langsung hilang dari sidebar untuk semua pengguna.',
                'Klik <strong>Simpan Pengaturan</strong>.',
            ],
            'catatan' => 'Mematikan modul tidak menghapus data apa pun; menyalakannya kembali memunculkan datanya utuh. Menu Santri, Kelas, dan Kamar sengaja tidak bisa dimatikan karena jadi inti sistem.',
            'anchor' => 'pengaturan',
        ],

        Pages\BillingPage::class => [
            'judul' => 'Informasi Langganan',
            'ringkas' => 'Status paket, kuota santri, dan tanggal berakhir langganan pesantren.',
            'langkah' => [
                'Periksa paket aktif, kuota santri, dan tanggal berakhirnya.',
                'Klik <strong>Upgrade / Perpanjang Paket</strong> untuk menaikkan paket atau memperpanjang masa aktif.',
                'Bila ada order yang sedang berjalan, detail dan statusnya tampil di halaman ini.',
            ],
            'catatan' => 'Menurunkan paket tidak menghapus data — modul yang tidak tersedia hanya terkunci sementara.',
            'anchor' => 'pengaturan',
        ],

        Pages\UpgradePage::class => [
            'judul' => 'Upgrade / Perpanjang Paket',
            'ringkas' => 'Memilih paket dan durasi langganan, lalu menerbitkan invoice.',
            'langkah' => [
                'Pilih <strong>Paket Tujuan</strong>. Untuk paket Maju, atur juga kuota santri.',
                'Pilih <strong>Durasi Langganan</strong> — bila sisa masa aktif masih panjang, sistem mewajibkan durasi minimum yang lebih lama.',
                'Masukkan <strong>Kode Kupon</strong> bila ada, lalu klik Terapkan untuk memvalidasi diskonnya.',
                'Klik <strong>Lakukan Pembayaran</strong> — Anda diarahkan ke halaman invoice.',
            ],
            'anchor' => 'pengaturan',
        ],

        Pages\OrderInvoicePage::class => [
            'judul' => 'Invoice Pembayaran',
            'ringkas' => 'Rincian tagihan langganan dan tempat mengunggah bukti transfer.',
            'langkah' => [
                'Transfer sesuai nominal ke salah satu rekening yang tertera.',
                'Unggah <strong>bukti transfer</strong> (JPG/PNG/PDF, maks 5 MB), lalu klik tombol kirim di bawah formulir.',
                'Tim platform memverifikasi dalam 1×24 jam; paket baru aktif otomatis setelah dikonfirmasi.',
            ],
            'anchor' => 'pengaturan',
        ],

        // ---------------------------------------------- Grup Platform (super admin)
        Resources\Pesantrens\PesantrenResource::class => [
            'judul' => 'Pesantren',
            'ringkas' => 'Seluruh pesantren pelanggan beserta paket dan masa aktifnya.',
            'langkah' => [
                'Klik <strong>+ New</strong> untuk mendaftarkan pesantren baru: nama, slug, paket, kuota santri, status, dan tanggal expired.',
                'Slug menentukan alamat portal pesantren, jadi pastikan benar sejak awal.',
                'Gunakan aksi baris untuk mengubah status berlangganan atau memperpanjang masa aktif.',
            ],
        ],

        Resources\DemoRequests\DemoRequestResource::class => [
            'judul' => 'Antrean Demo',
            'ringkas' => 'Permintaan demo yang masuk dari halaman publik, beserta SLA tindak lanjutnya.',
            'langkah' => [
                'Buka <strong>Detail</strong> untuk melihat kontak dan kebutuhan pemohon.',
                'Hubungi pemohon, lalu klik <strong>Tandai Dihubungi</strong>.',
                'Perhatikan penyaring <strong>Overdue (SLA)</strong> — itu antrean yang sudah lewat batas waktu tindak lanjut.',
                'Kolom <strong>Duplikat</strong> menandai permintaan berulang dari pesantren yang sama.',
            ],
        ],

        Resources\MasterPengumumanCentral\MasterPengumumanCentralResource::class => [
            'judul' => 'Pengumuman Platform',
            'ringkas' => 'Pengumuman yang tampil di dasbor seluruh pesantren sekaligus.',
            'langkah' => [
                'Klik <strong>+ New</strong>, isi judul dan isi pengumuman.',
                'Nyalakan <strong>Aktif &amp; Tampilkan</strong> agar tampil di dasbor semua tenant.',
                'Matikan togglenya untuk menurunkan pengumuman tanpa menghapusnya.',
            ],
        ],

        // --------------------------------------------- Grup Langganan (super admin)
        Resources\Orders\OrderResource::class => [
            'judul' => 'Pesanan Upgrade',
            'ringkas' => 'Order langganan dari pesantren yang menunggu verifikasi pembayaran.',
            'langkah' => [
                'Buka order, klik <strong>Lihat Bukti Transfer</strong> untuk memeriksa buktinya.',
                'Bila sah, klik <strong>Konfirmasi Order</strong> — paket pesantren otomatis aktif.',
                'Bila tidak sah, klik <strong>Tolak Order</strong> dan isi alasan penolakannya.',
                'Untuk pembayaran di luar sistem, pakai <strong>Konfirmasi Langsung</strong> dan catat metodenya — catatan wajib diisi.',
            ],
        ],

        Resources\Kupons\KuponResource::class => [
            'judul' => 'Kupon Diskon',
            'ringkas' => 'Kode diskon yang bisa dipakai pesantren saat upgrade atau perpanjangan.',
            'langkah' => [
                'Klik <strong>+ New</strong>, isi <strong>Kode Kupon</strong>, tipe diskon, dan nilai diskonnya.',
                'Batasi pemakaiannya lewat minimal durasi berlangganan, maksimal penggunaan total, dan tanggal berlaku hingga.',
                'Nyalakan <strong>Kupon aktif</strong>, lalu simpan.',
            ],
            'catatan' => 'Matikan toggle aktif untuk menghentikan kupon tanpa menghapus riwayat pemakaiannya.',
        ],

        Resources\PlatformBankAccounts\PlatformBankAccountResource::class => [
            'judul' => 'Rekening Bank Platform',
            'ringkas' => 'Rekening tujuan transfer yang ditampilkan di invoice langganan.',
            'langkah' => [
                'Klik <strong>+ New</strong>, isi nama bank, nomor rekening, dan atas nama.',
                'Unggah logo bank dan atur urutan tampilnya bila perlu.',
                'Nyalakan <strong>Aktif</strong> agar rekening muncul di halaman invoice.',
            ],
        ],

        // ------------------------------- Grup Pengaturan Platform (super admin)
        Pages\RegistrationSettingsPage::class => [
            'judul' => 'Pengaturan Pendaftaran',
            'ringkas' => 'Tuas buka-tutup pendaftaran mandiri dan permintaan demo.',
            'langkah' => [
                'Nyalakan atau matikan pendaftaran mandiri dan permintaan demo di halaman publik.',
                'Simpan — perubahannya langsung berlaku tanpa deploy.',
            ],
        ],

        Pages\EmailSettingsPage::class => [
            'judul' => 'Pengaturan Email',
            'ringkas' => 'Kredensial gateway pengiriman email platform.',
            'langkah' => [
                'Isi kredensial gateway email yang dipakai.',
                'Simpan, lalu kirim email uji untuk memastikan sambungannya berhasil.',
            ],
        ],

        Pages\WhatsAppSettingsPage::class => [
            'judul' => 'Pengaturan WhatsApp',
            'ringkas' => 'Kredensial gateway WhatsApp beserta template pesannya.',
            'langkah' => [
                'Isi kredensial gateway WhatsApp.',
                'Sunting template pesan sesuai kebutuhan — template inilah yang dipakai notifikasi otomatis.',
                'Simpan.',
            ],
        ],

        Pages\AnalyticsSettingsPage::class => [
            'judul' => 'Pengaturan Analytics',
            'ringkas' => 'Pemasangan Google Tag Manager atau GA4 untuk seluruh platform.',
            'langkah' => [
                'Isi ID container GTM atau GA4.',
                'Nyalakan pelacakan, lalu simpan.',
                'Skrip hanya dimuat bila pelacakan aktif dan ID-nya terisi.',
            ],
        ],

        Pages\PlatformLogoSettingsPage::class => [
            'judul' => 'Merek &amp; Kontak Platform',
            'ringkas' => 'Logo, favicon, dan nomor WhatsApp dukungan yang dipakai seluruh panel.',
            'langkah' => [
                'Unggah logo dan favicon platform.',
                'Isi <strong>nomor WhatsApp dukungan</strong> — nomor inilah yang dituju tombol Bantuan di topbar.',
                'Simpan.',
            ],
            'catatan' => 'Bila nomor dukungan dikosongkan, tombol Bantuan di topbar tidak akan muncul sama sekali.',
        ],

        Pages\BillingSettingsPage::class => [
            'judul' => 'Pengaturan Harga Langganan',
            'ringkas' => 'Daftar harga paket dan durasi yang ditawarkan ke pesantren.',
            'langkah' => [
                'Sunting harga tiap paket dan durasi langganan.',
                'Simpan — harga baru langsung dipakai halaman Upgrade dan halaman harga publik.',
            ],
        ],
    ];

    /**
     * Komponen panel yang SENGAJA tidak punya panduan, beserta alasannya.
     *
     * Ada supaya "belum ditulis" bisa dibedakan dari "memang tidak perlu".
     * TombolPanduanTest menuntut setiap Resource/Page terdaftar di salah satu
     * dari dua konstanta ini, sehingga menu baru tidak bisa lolos diam-diam.
     *
     * @var array<class-string>
     */
    public const TANPA_PANDUAN = [
        // Cluster ikut terdaftar sebagai "page" di panel, tapi halamannya tidak
        // pernah dirender: Filament\Clusters\Cluster me-redirect ke item navigasi
        // pertama begitu dibuka. Tidak ada header di situ, jadi tidak ada tempat
        // untuk tombolnya — panduannya tinggal di menu-menu di dalamnya.
        Clusters\Santri::class,
        Clusters\Akademik::class,
        Clusters\Tahfidz::class,
        Clusters\Presensi::class,
        Clusters\Kesantrian::class,
        Clusters\Keuangan::class,
    ];

    /**
     * Entri panduan untuk halaman yang sedang dirender, atau null bila tidak ada.
     *
     * $scopes datang langsung dari Filament: ViewManager::renderHook() memanggil
     * closure hook lewat app()->call(..., ['scopes' => $scopes]), dan isinya
     * persis yang dikembalikan halaman lewat getRenderHookScopes() —
     * [KelasHalaman] untuk Page biasa, [KelasHalaman, KelasResource] untuk
     * halaman resource.
     *
     * Dipakai alih-alih Livewire::current() karena yang dibutuhkan cuma identitas
     * kelas, dan cara ini memberi dua hal gratis: aturan "List/View ikut panduan
     * resource induknya", serta presedensi yang benar — entri atas nama kelas
     * halaman menang atas entri resource, sehingga sub-halaman yang suatu saat
     * butuh panduan sendiri tinggal ditambahkan tanpa mengubah kode apa pun.
     *
     * @param  array<string>  $scopes
     */
    public static function untukScope(array $scopes): ?array
    {
        foreach ($scopes as $kelas) {
            if ($entri = static::untuk($kelas)) {
                return $entri;
            }
        }

        return null;
    }

    /**
     * @param  class-string  $kelas
     */
    public static function untuk(string $kelas): ?array
    {
        return static::PETA[$kelas] ?? null;
    }
}
