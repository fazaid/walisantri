# Panduan Penggunaan Walisantri — Admin Pesantren & Ustadz

Panduan ini ditulis untuk **Admin Pesantren** dan **Ustadz** yang menggunakan aplikasi Walisantri sehari-hari. Bahasanya sengaja dibuat sederhana, langkah demi langkah — tidak perlu latar belakang teknis untuk mengikutinya.

> Untuk panduan Wali Santri, lihat Lampiran di bagian akhir dokumen ini.

## Daftar Isi

0. [Pendahuluan](#0-pendahuluan)
1. [Dashboard](#1-dashboard)
2. [Manajemen Santri](#2-manajemen-santri)
3. [Manajemen Pengguna](#3-manajemen-pengguna)
4. [Modul Tahfidz](#4-modul-tahfidz)
5. [Modul Mutaba'ah](#5-modul-mutabaah)
6. [Modul Kesantrian](#6-modul-kesantrian)
7. [Modul Akademik](#7-modul-akademik)
8. [Modul Presensi](#8-modul-presensi)
9. [Rapor](#9-rapor)
10. [Keuangan](#10-keuangan)
11. [Pengumuman](#11-pengumuman)
12. [Magic Link — Akses Portal Wali](#12-magic-link--akses-portal-wali)
13. [Preview Portal Wali](#13-preview-portal-wali)
14. [Pengaturan Pesantren & Langganan](#14-pengaturan-pesantren--langganan)
15. [Troubleshooting Umum](#15-troubleshooting-umum)
16. [Lampiran: Apa yang Dilihat Wali Santri](#16-lampiran-apa-yang-dilihat-wali-santri)

---

## 0. Pendahuluan

**Walisantri** adalah aplikasi untuk mencatat dan memantau perkembangan santri — tahfidz, mutaba'ah harian, akademik, kesehatan, karakter, keuangan (SPP & uang saku), sampai laporan ke wali santri.

### Cara Login

Buka `app.walisantri.com/admin`, lalu login dengan email/password yang diberikan admin pesantren Anda. Setelah login, panel yang tampil sama untuk semua peran — hanya menu di sisi kiri yang berbeda tergantung peran Anda.

### Ringkasan Peran

| Peran | Ruang lingkup akses |
|---|---|
| **Admin Pesantren** | Akses hampir penuh atas seluruh data pesantrennya sendiri: kelola santri, kelas, kamar, semua modul pencatatan, keuangan, pengguna, pengumuman, dan pengaturan langganan. |
| **Ustadz** | Akses ke santri **bimbingannya sendiri** (halaqah) saja untuk sebagian besar modul. Tidak bisa menghapus data, tidak bisa mengakses menu Keuangan, Kelas/Kamar, dan Pengaturan. **Modul Presensi punya aturan tersendiri:** yang dipakai bukan halaqah, melainkan penugasan **wali kelas** (untuk presensi harian) dan **pengampu mapel** (untuk presensi per jam) — lihat bagian 8. |

Aturan pentingnya sederhana: **Admin bisa apa saja di pesantrennya. Ustadz hanya bisa lihat/isi data untuk santri yang dibimbingnya, dan tidak pernah bisa menghapus data.**

---

## 1. Dashboard

**Siapa yang bisa akses:** Admin & Ustadz (tampilan berbeda)

Begitu login, Anda akan langsung melihat Dashboard dengan beberapa kartu statistik ringkas:

**Dashboard Admin** menampilkan:
- Jumlah santri aktif vs kuota paket langganan
- Jumlah ustadz & wali santri terdaftar
- Jumlah santri yang sedang sakit (istirahat total/rujukan luar) hari ini
- Persentase rata-rata amalan mutaba'ah minggu ini (seluruh santri)
- Status langganan pesantren (Trial/Aktif/Kadaluwarsa/Ditangguhkan) — klik kartu ini untuk langsung ke halaman Billing

**Dashboard Ustadz** menampilkan versi yang dipersempit ke santri bimbingannya sendiri:
- Jumlah santri binaan (halaqah)
- Jumlah setoran tahfidz yang sudah dicatat hari ini
- Jumlah santri binaan yang **belum** diisi mutaba'ah hari ini (jadi pengingat)
- Jumlah santri binaan yang sedang sakit

Di bawah kartu statistik biasanya ada grafik tren (tahfidz, nilai, SPP, dsb.) dan daftar pengumuman terbaru.

---

## 2. Manajemen Santri

Menu: **Santri** (di sidebar, cluster paling atas)

### 2.1 Data Santri

**Siapa yang bisa akses:** Admin (lihat semua, buat, ubah, hapus) · Ustadz (lihat semua santri pesantren, tapi hanya bisa **mengubah** data santri bimbingannya — tidak bisa membuat atau menghapus)

**Cara menambah santri baru (Admin):**
1. Buka menu **Santri → Santri**, klik tombol **+ New** (biasanya di kanan atas tabel).
2. Isi bagian **Data Santri**: NIS (harus unik), Nama Lengkap, Nama Panggilan (opsional), Tanggal Lahir, Jenis Kelamin, Kelas, Kamar, dan status Aktif (default aktif).
3. Isi **Biodata** (opsional tapi disarankan): nama ayah/ibu, alamat lengkap, jumlah saudara, cita-cita, ciri fisik.
4. Unggah **Foto Profil** kalau ada (JPG/PNG, maks 2 MB).
5. Di bagian **Relasi**, hubungkan santri dengan akun **Wali Santri** dan **Ustadz Pembimbing**. Ini penting — tanpa Wali Santri terhubung, Magic Link portal wali tidak bisa dibuat. Satu ustadz maksimal membimbing **20 santri aktif**; sistem akan menolak kalau sudah penuh.
6. Klik **Create** untuk menyimpan.

**Cara mengubah data santri (Admin & Ustadz):** klik ikon pensil (Edit) pada baris santri di tabel. Ustadz hanya akan melihat tombol Edit untuk santri bimbingannya sendiri.

**Aksi cepat lain yang tersedia per baris santri:**
- **Link Wali** — buka/salin link portal wali (lihat [bagian 12](#12-magic-link--akses-portal-wali))
- **Preview sebagai Wali** — lihat tampilan portal wali santri tersebut tanpa perlu login sebagai wali (lihat [bagian 13](#13-preview-portal-wali))
- **Regenerasi Link** (admin-only) — buat ulang link portal wali kalau link lama bocor/perlu diganti

**Aksi massal (pilih beberapa santri dengan centang, lalu):**
- **Pindah Kelas** — memindahkan sekaligus banyak santri ke kelas tujuan
- **Pindah Kamar** — sama, untuk kamar
- **Hapus** (admin-only)

**Filter tabel santri:** jenis kelamin, kelas, kamar, status aktif/non-aktif, dan data yang sudah dihapus (Trashed).

### 2.2 Kelas & Kamar

**Siapa yang bisa akses:** Admin saja (`Santri → Kelas` dan `Santri → Kamar`)

Ini adalah data master — buat dulu daftar Kelas dan Kamar sebelum menambahkan santri, supaya bisa langsung dipilih di form Santri.
- **Kelas**: cukup isi Nama Kelas (harus unik dalam satu pesantren).
- **Kamar**: isi Nama Kamar dan Kapasitas (isi `0` kalau tidak ingin membatasi kapasitas).

### 2.3 Prestasi Santri

**Siapa yang bisa akses:** Admin (CRUD penuh) · Ustadz (lihat, tambah, ubah — tidak bisa hapus)

Menu: **Santri → Prestasi**. Gunakan untuk mencatat pencapaian lomba/kejuaraan santri.

1. Klik **+ New**, pilih Santri dan Tanggal Prestasi.
2. Isi Judul/Nama Lomba (contoh: "Juara 1 MTQ Cabang Tilawah"), Kategori, Tingkat (internal s.d. internasional), Posisi/Peringkat (opsional), Penyelenggara, dan Keterangan.
3. Unggah sertifikat/foto piala kalau ada (JPG/PNG/PDF, maks 5 MB).
4. Simpan. Prestasi ini akan otomatis muncul di portal wali santri terkait.

---

## 3. Manajemen Pengguna

Menu: **Pengguna** (grup Manajemen) — **Admin saja**

Gunakan menu ini untuk membuat akun **Ustadz** dan **Wali Santri** baru (juga admin pesantren lain kalau perlu).

**Cara membuat akun baru:**
1. Buka **Pengguna**, klik **+ New**.
2. Isi Nama, Email (boleh dikosongkan khusus untuk role Wali Santri jika wali hanya punya nomor WhatsApp — magic link tetap bisa dipakai tanpa email), dan No. Telepon.
3. Pilih **Role**: Admin Pesantren, Ustadz, atau Wali Santri.
4. Isi Password (minimal 8 karakter) dan Konfirmasi Password.
5. Klik **Create**.

Admin hanya bisa melihat & mengelola pengguna di pesantrennya sendiri.

> **Tip:** Setelah membuat akun ustadz baru, jangan lupa hubungkan ustadz tersebut ke santri yang akan dibimbingnya lewat form Edit Santri → bagian Relasi.

---

## 4. Modul Tahfidz

Menu: **Tahfidz** — **Admin & Ustadz**

### 4.1 Setoran

Catat hafalan harian santri. Menu: **Tahfidz → Setoran**.

1. Klik **+ New**. Pilih Santri dan Ustadz Pencatat (otomatis terisi nama Anda kalau login sebagai ustadz).
2. Pilih Tanggal Setoran dan **Tipe Setoran**: Sabaq (hafalan baru), Sabqi (hafalan kemarin), atau Manzil (hafalan lama).
3. Isi **Halaman Mulai** dan **Halaman Selesai** yang disetorkan (perhitungan capaian juz di sistem berbasis halaman mushaf, maksimal halaman 600 = Juz 30). Surah terakhir yang disetorkan bersifat opsional.
4. Isi **Nilai Kelancaran**: Mumtaz (sangat baik), Jayyid Jiddan (baik sekali), Jayyid (baik), atau Maqbul (cukup), plus Catatan Evaluasi (opsional).
5. Simpan.

Ustadz hanya bisa mencatat/mengubah setoran untuk santri bimbingannya, dan tidak bisa menghapus data setoran (hanya admin yang bisa).

### 4.2 Ujian

Catat hasil ujian kenaikan juz. Menu: **Tahfidz → Ujian**.

1. Klik **+ New**. Pilih Santri, Penguji, Tanggal Ujian, Target Juz yang diuji, dan Status Kelulusan (Lulus/Mengulang).
2. Isi **Periode Rapor**: Tahun Ajaran, Periode (Bulanan/Semester Ganjil/Semester Genap — kalau Bulanan, pilih Bulan-nya juga). Ini menentukan di rapor periode mana hasil ujian ini akan muncul.
3. Isi **Penilaian**: Nilai Hafalan (bebas, misal skor angka), Tilawah/Makhraj/Tajwid (skala A–D), dan Rekomendasi Pembimbing (wajib diisi).
4. Simpan.

### 4.3 Rapor Tahfidz

Menu: **Tahfidz → Rapor** — ringkasan capaian tahfidz per santri, per periode (bisa juga diakses lewat Cluster Rapor, lihat [bagian 9](#9-rapor)). Pilih santri dan periode, sistem menampilkan total setoran, total juz tercapai, hari aktif setor, distribusi nilai, dan hasil ujian. Tersedia tombol **Unduh PDF**.

---

## 5. Modul Mutaba'ah

Menu: **Mutabaah** — **Admin & Ustadz**

Mutaba'ah adalah catatan amalan harian santri (sholat, tilawah, dsb.) — daftarnya bisa disesuaikan per pesantren.

### 5.1 Amal Master (daftar amalan yang dinilai)

**Admin saja.** Menu: **Mutabaah → Amal Master**. Di sinilah Anda menentukan amalan apa saja yang dicatat setiap hari.

1. Klik **+ New**. Isi Nama Amal (contoh: "Sholat Berjamaah"), Ikon emoji (opsional), dan **Tipe Penilaian**:
   - **Centang** (dikerjakan/tidak) — cocok untuk amalan ya/tidak seperti puasa sunnah.
   - **Hitungan** (misal 0–5 waktu) — cocok untuk amalan yang dihitung, seperti jumlah sholat berjamaah dalam sehari. Kalau pilih ini, isi juga Nilai Maksimal.
2. Isi Satuan (default "hari"), Bobot Poin (kontribusi amal ini terhadap skor harian — makin besar makin berpengaruh), dan Urutan Tampil.
3. Toggle **Aktif** — amal nonaktif tidak muncul lagi di form input harian, tapi riwayat lama tetap tersimpan.
4. Simpan.

### 5.2 Isi Harian (input massal per hari)

Menu: **Mutabaah → Isi Harian** — cara tercepat mengisi mutaba'ah banyak santri sekaligus dalam satu hari.

1. Pilih **Tanggal** di atas. Sistem otomatis menampilkan daftar santri (Admin: semua santri aktif; Ustadz: hanya santri bimbingannya) beserta form amalan untuk tanggal tersebut — kalau data hari itu sudah pernah diisi sebelumnya, nilainya otomatis muncul kembali (bisa diubah).
2. Untuk tiap santri, isi kolom **Udzur** (Tidak/Sakit/Haid/Izin Pulang/Tugas Pondok) dan nilai tiap amalan (toggle untuk tipe Centang, angka untuk tipe Hitungan).
3. Klik **Simpan Semua** di bagian bawah — semua baris tersimpan sekaligus.

> **Catatan:** Kalau status Udzur santri diubah jadi "Sakit" atau ada rekam kesehatan bertipe "Istirahat Total"/"Rujukan Luar" pada tanggal yang sama, statusnya akan saling sinkron secara otomatis.

### 5.3 Mutabaah (log per-santri, entri satuan)

Menu: **Mutabaah → Mutabaah** — alternatif form untuk mengisi/mengubah satu entri mutaba'ah per santri per tanggal (berguna untuk koreksi data satu-satu, bukan input massal harian).

---

## 6. Modul Kesantrian

Menu: **Kesantrian** — **Admin & Ustadz** (kecuali disebut lain)

### 6.1 Karakter (Rapor Karakter)

Menu: **Kesantrian → Karakter**. Penilaian adab dan kepribadian santri per periode.

1. Klik **+ New**. Pilih Santri, Tahun Ajaran, Periode (Bulanan/Semester), Bulan (kalau Bulanan), dan Tanggal Input.
2. Isi **Penilaian Adab** (skala A–D): Adab ke Ustadz, Adab ke Tamu, Adab Asrama, Adab Kelas, Adab Sholat, Adab Al-Quran, Adab Minum.
3. Isi **Penilaian Kepribadian** (skala A–D): Tanggung Jawab, Kemandirian, Kepatuhan, Kebersihan, Mengelola Diri, Kepedulian, Empati, Kebersamaan, Kedisiplinan.
4. Isi **Log Kasus Khusus** kalau ada catatan pelanggaran/kejadian penting (opsional).
5. Simpan. Sistem menolak entri duplikat untuk kombinasi santri + periode yang sama.

### 6.2 Kesehatan

Menu: **Kesantrian → Kesehatan**. Rekam medis santri, tersedia di semua paket langganan.

1. Klik **+ New**. Pilih Santri dan Tanggal Periksa (satu santri hanya bisa punya satu rekam per tanggal).
2. Isi Berat Badan & Tinggi Badan (opsional).
3. Pilih **Jenis Rekam**: "Keluhan Sakit" atau "Pemeriksaan Rutin". Kalau pilih Pemeriksaan Rutin, bagian Keluhan & Tindakan di bawah otomatis disembunyikan (tidak wajib diisi).
4. Untuk Keluhan Sakit, isi Kategori Keluhan (Demam, Batuk/Pilek, Sakit Perut, Pusing, Kulit Gatal, Luka Fisik, Lainnya), Detail Keluhan, Tindakan & Obat yang diberikan, dan **Status Pemulihan**: Rawat Mandiri, Istirahat Total, Rujukan Luar, atau Sembuh.
5. Kalau Status Pemulihan dipilih "Sembuh", isi juga Tanggal Sembuh.
6. Simpan.

> **Efek samping penting:** kalau status santri diset "Istirahat Total" atau "Rujukan Luar", statusnya otomatis memicu status udzur "Sakit" di catatan mutaba'ah tanggal yang sama, dan santri tersebut akan muncul di kartu "Santri Sakit" pada Dashboard.

### 6.3 Inventaris

Menu: **Kesantrian → Inventaris** — **hanya tersedia untuk paket Maju**. Mencatat barang milik santri yang dititipkan di pesantren.

1. Klik **+ New**. Pilih Santri, isi Nama Barang, Kode Unik Fisik (harus unik, contoh format: `FZ-SRG-01`), Kuota Maks (batas jumlah barang jenis ini), Kondisi (Baik/Layak Pakai-Rusak Ringan/Hilang), dan Tanggal Sidak Terakhir (opsional).
2. Simpan.

---

## 7. Modul Akademik

Menu: **Akademik** — sebagian **Admin saja**, sebagian **Admin & Ustadz**

### 7.1 Mata Pelajaran (master)

**Admin saja.** Menu: **Akademik → Mata Pelajaran**. Daftarkan mata pelajaran per kelas beserta ustadz pengampunya (satu ustadz = satu mapel tetap; ustadz nanti hanya bisa input nilai untuk mapel yang diampunya).

1. Klik **+ New**. Pilih Kelas, Ustadz Pengampu (opsional), isi Nama Mata Pelajaran.
2. Simpan.

### 7.2 Nilai Akademik

**Admin & Ustadz** (ustadz dibatasi ke mapel yang ia ampu). Menu: **Akademik → Nilai Akademik**.

1. Klik **+ New**. Pilih **Mata Pelajaran** dulu — daftar Santri di dropdown berikutnya otomatis menyesuaikan kelas dari mapel yang dipilih.
2. Pilih Santri, Tahun Ajaran, Periode, Bulan (kalau Bulanan).
3. Isi Nilai (0–100) dan Catatan (opsional).
4. Simpan. Rapor akademik dihitung otomatis dari kumpulan nilai ini (tidak perlu direkap manual), lihat [bagian 9](#9-rapor).

### 7.3 Ekstrakurikuler

**Ekskul (master)** — **Admin saja**, menu **Akademik → Ekskul**: daftarkan nama ekskul, Nama Pembina, Deskripsi, dan status Aktif.

**Ekskul Santri (partisipasi)** — **Admin & Ustadz**, menu **Akademik → Ekskul Santri**: catat santri mana yang ikut ekskul apa.

1. Klik **+ New**. Pilih Santri dan Ekskul.
2. Pilih **Level**: Pemula, Menengah, atau Mahir.
3. Isi Tanggal Mulai, dan toggle Aktif (aktifkan/nonaktifkan keikutsertaan tanpa perlu menghapus riwayat).
4. Simpan.

---

## 8. Modul Presensi

Menu: **Presensi** — **Admin & Ustadz** (ustadz dibatasi ke kelas yang ia walikan; untuk presensi per jam pelajaran, ke mapel yang ia ampu)

Modul ini mencatat kehadiran santri dengan tujuh status: **Hadir · Sakit · Izin · Alpa · Terlambat · Pulang · Dispensasi**. "Pulang" untuk izin pulang bermalam, "Dispensasi" untuk santri yang sedang bertugas atau mengikuti lomba.

> **Siapa boleh mengisi apa.** Presensi harian diisi **wali kelas** (kelas yang ia walikan) atau admin. Presensi per jam pelajaran diisi **ustadz pengampu** mapel tersebut atau admin. Pembimbing halaqah tidak punya akses ke modul ini — kalau seorang ustadz perlu mengabsen, jadikan ia wali kelas lewat menu **Santri → Kelas**.

### 8.1 Pengaturan Presensi (lakukan sekali di awal)

**Admin saja.** Dari daftar **Presensi → Kehadiran**, klik tombol **Pengaturan** di kanan atas.

1. **Jam masuk** — patokan jam untuk menentukan keterlambatan (mis. 07:00).
2. **Toleransi terlambat (menit)** — santri yang tercatat setelah jam masuk + toleransi otomatis berstatus **Terlambat** (mis. 15 menit).
3. **Hari libur mingguan** — centang hari yang memang tidak ada kegiatan (mis. Jumat, atau Minggu). Hari ini tidak akan dihitung sebagai hari efektif di rekap.
4. **Aktifkan presensi per jam pelajaran** — biarkan mati kalau pesantren hanya butuh absen harian. Menyalakannya memunculkan menu tambahan; lihat bagian 8.6.
5. **Aktifkan pengajuan izin oleh wali** — kalau dimatikan, hanya admin/ustadz yang bisa mencatat izin.
6. **Batas edit ustadz (hari)** — berapa hari ke belakang ustadz masih boleh mengisi atau memperbaiki presensi (bawaan 7 hari). Admin tidak terkena batas ini dan bisa memperbaiki tanggal kapan pun. Isi `0` kalau Anda ingin ustadz bebas mengedit tanpa batas waktu.

> **Kenapa ada batas waktu?** Wali santri membaca catatan kehadiran anaknya. Kalau catatan alpa bulan lalu bisa diubah diam-diam, satu keluhan orang tua akan sulit ditelusuri. Batas ini membuat koreksi wajar (santri ternyata izin, ustadz lupa mencatat kemarin) tetap mudah, sementara perubahan yang jauh ke belakang harus lewat admin — dan tercatat siapa yang melakukannya.

### 8.2 Kehadiran (isi presensi harian)

Menu: **Presensi → Kehadiran**, lalu klik tombol **Isi Presensi** di kanan atas.

1. Pilih **Tanggal**, lalu pilih **Kelompok**:
   - **Kelas** — pilih satu kelas. Ini pilihan yang biasa dipakai, dan satu-satunya yang tersedia untuk ustadz (terbatas kelas yang ia walikan).
   - **Semua santri aktif** *(admin saja)* — seluruh santri pesantren dalam satu layar, berguna untuk pondok kecil atau kegiatan gabungan.
   - **Belum punya kelas** *(admin saja)* — santri yang belum terdaftar di kelas mana pun, lengkap dengan jumlahnya.

   Semua santri di kelompok terpilih langsung tampil.

> **Kenapa ada pilihan "Belum punya kelas"?** Kelas bersifat opsional di data santri — santri yang diimpor massal sebelum data Kelas dibuat, atau yang kelasnya dihapus, akan kehilangan kelasnya. Tanpa pilihan ini mereka tidak akan pernah bisa diabsen dan tidak terlihat di mana pun. Kalau angkanya besar, sebaiknya rapikan dulu lewat menu **Santri** (tersedia filter **Tanpa Kelas** di daftar santri).
2. Semua santri **sudah tercentang Hadir**. Anda hanya perlu mengubah yang tidak hadir — itu jauh lebih cepat daripada mencentang satu per satu.
3. Ubah status santri yang perlu, isi **Catatan** bila ada (mis. "menyusul, ke poliklinik").
4. Klik **Simpan**. Menyimpan ulang untuk tanggal yang sama akan **memperbarui**, bukan membuat data ganda.

Kalau tanggal yang dipilih terdaftar sebagai hari libur, akan muncul peringatan kuning — halaman tetap bisa diisi (kadang ada kegiatan di hari libur), peringatan itu hanya supaya Anda tidak salah tanggal.

> **Kenapa tidak ada "otomatis Alpa" di akhir hari?** Karena sistem tidak bisa membedakan santri yang benar-benar bolos dari ustadz yang lupa mengisi. Kalau ditandai otomatis, satu ustadz yang sakit bisa membuat seluruh kelas tercatat Alpa — dan wali santri melihatnya. Karena itu hari yang tidak diisi muncul di rekap sebagai **"Tanpa Keterangan"**, bukan Alpa. Kalau Anda memang ingin menutup hari itu, gunakan tombol **Tutup Hari** di halaman Kehadiran: sisa santri yang belum punya catatan akan ditandai Alpa atas nama Anda.

### 8.3 Hari Libur (kalender satu tahun ajaran)

**Admin saja.** Menu: **Presensi → Hari Libur**.

1. Klik **Tambah Hari Libur**.
2. Isi **Tanggal Mulai** dan **Tanggal Selesai** — untuk libur satu hari, isi tanggal yang sama di keduanya.
3. Isi **Keterangan** (mis. "Maulid Nabi", "Libur Akhir Semester Ganjil") dan pilih **Tahun Ajaran**.
4. Simpan. Rentang tanggal otomatis dipecah menjadi baris per hari, dan sistem memberi tahu berapa hari yang tersimpan.

Beberapa hal yang memudahkan:

- **Salah tanggal? Simpan ulang saja.** Menyimpan rentang yang menimpa libur yang sudah ada akan **memperbarui keterangannya**, bukan menolak. Jadi mengoreksi cukup dengan menyimpan rentang yang benar.
- **Tanggal terbalik tetap aman.** Kalau tanggal selesai lebih awal daripada tanggal mulai, sistem menukarnya sendiri.
- **Menghapus libur sepekan** berarti mencentang barisnya di daftar lalu memakai hapus massal — konsekuensi dari penyimpanan per hari.

Hari libur dan hari libur mingguan (bagian 8.1) sama-sama dikeluarkan dari perhitungan **hari efektif** di rekap — jadi persentase kehadiran tidak turun hanya karena pondok sedang libur.

> **Mengisi presensi di hari libur tetap boleh.** Kalau Anda memilih tanggal yang terdaftar libur di halaman Isi Presensi, akan muncul peringatan kuning — tapi tombol simpannya tetap aktif. Ini disengaja: ada pondok yang tetap berkegiatan di hari libur (kajian, kerja bakti, lomba). Peringatan itu untuk mencegah kesalahan yang jauh lebih umum, yaitu salah memilih tanggal.

### 8.4 Kartu QR & Absen dengan Pemindai

Cara tercepat mengabsen banyak santri: cetak kartu ber-QR sekali, lalu tinggal dipindai tiap pagi. Santri tidak perlu membawa ponsel.

**Mencetak kartu.** Dari **Presensi → Kehadiran**, klik **Cetak Kartu**, pilih kelas, lalu unduh PDF-nya dan cetak. Setiap kartu memuat nama, NIS, dan kode QR santri.

**Memindai.** Klik **Scan QR** dari halaman yang sama.

1. Kolom pemindaian langsung aktif. Kalau Anda memakai **alat pemindai (scanner) USB atau Bluetooth**, cukup arahkan dan tembak — alat itu bekerja seperti papan ketik, jadi tidak perlu pengaturan apa pun.
2. Setiap pemindaian langsung tercatat **Hadir**, atau **Terlambat** bila sudah lewat jam masuk + toleransi. Nama santri dan jamnya muncul di layar sebagai konfirmasi.
3. Kartu tertinggal? Ketik saja **NIS** santri di kolom yang sama, lalu tekan Enter.
4. Memindai kartu yang sama dua kali tidak membuat data ganda — akan muncul keterangan "sudah tercatat", lengkap dengan jam pencatatan pertamanya.

> **Kartu hilang atau terlanjur difoto orang lain?** Buka detail santri, klik **Regenerasi Kode Presensi**, lalu cetak ulang kartunya. Kode lama langsung tidak berlaku.

> **Kode QR di kartu ini bukan link portal wali.** Keduanya sengaja dipisah: kode presensi tidak bisa dipakai membuka data santri, hanya bisa dipindai oleh petugas yang sudah login. Jadi kartu yang terjatuh atau terfoto tidak membocorkan nilai, rekam medis, atau tagihan SPP.

### 8.5 Pengajuan Izin

Menu: **Presensi → Pengajuan Izin**. Angka di sebelah menu menunjukkan jumlah pengajuan yang belum diproses.

**Wali mengajukan sendiri.** Wali santri mengisi dari portal: memilih anak, jenis izin (Sakit / Izin / Pulang / Dispensasi), rentang tanggal, alasan, dan lampiran seperti foto surat dokter. Pengajuan masuk dengan status **Menunggu Persetujuan**.

1. Buka pengajuan, baca alasan dan lihat lampirannya.
2. Klik **Setujui** atau **Tolak**, tambahkan catatan bila perlu.
3. Begitu disetujui, presensi untuk tanggal-tanggal tersebut **terisi otomatis** — tidak perlu dicatat ulang di menu Kehadiran. Hari libur di dalam rentang dilewati.

**Admin mencatat langsung.** Kalau santri melapor lisan, klik **+ New** di menu yang sama. Izin yang dibuat admin langsung berstatus disetujui dan langsung mengisi presensi.

### 8.6 Presensi per Jam Pelajaran (opsional)

Muncul hanya bila diaktifkan di Pengaturan (bagian 8.1). Berguna untuk pesantren yang punya kelas formal dan ingin tahu di jam ke berapa santri mulai tidak masuk.

**Atur jam pelajaran dulu.** Dari halaman Kehadiran, klik **Jam Pelajaran** (**Admin saja**). Isi jam ke-1 sampai jam terakhir beserta jam mulai dan selesainya. Delapan jam pelajaran standar sudah tersedia dan tinggal disesuaikan.

**Mengisi.** Klik **Isi per Jam** dari halaman Kehadiran, pilih Tanggal, Kelas, Mata Pelajaran, dan Jam ke berapa. Cara pengisiannya sama persis dengan presensi harian.

### 8.7 Rekap & Ekspor

Menu: **Presensi → Rekap**.

1. Pilih **Tahun Ajaran** dan **Periode** (Bulanan / Semester Ganjil / Semester Genap).
2. Pilih tampilan: **per kelas**, **per santri**, atau **per jam pelajaran**.
3. Tabel menampilkan jumlah tiap status, **Hari Efektif**, kolom **Tanpa Keterangan**, dan **% Kehadiran** (Hadir + Terlambat + Dispensasi dihitung hadir).
4. Klik **Ekspor Excel** di kanan atas untuk mengunduh. Isinya persis angka yang tampil di layar, mengikuti filter yang sedang aktif.

Kolom **Tanpa Keterangan** yang besar di satu kelas biasanya bukan berarti santrinya bolos — melainkan presensi kelas itu belum diisi. Gunakan kolom ini untuk memantau kedisiplinan pencatatan, bukan kedisiplinan santri.

Bedanya dengan **Alpa**: Alpa berarti seseorang menyatakan santri itu tidak hadir tanpa keterangan; Tanpa Keterangan berarti tidak ada yang menyatakan apa pun. Sistem sengaja tidak menebak — lihat catatan di bagian 8.2.

Di halaman ini juga ada panel **Perlu Perhatian**: daftar santri dengan **3 hari alpa berturut-turut atau lebih**. Gunakan sebagai peringatan dini untuk menghubungi wali sebelum masalahnya membesar.

> "Berturut-turut" dihitung atas **hari efektif**, bukan hari kalender. Jadi alpa hari Jumat lalu alpa hari Senin tetap terhitung dua kali berturut-turut kalau Sabtu–Minggu memang libur — akhir pekan tidak memutus rangkaian.

**Di Dashboard** juga ada kartu **Kehadiran Hari Ini** (untuk Admin dan Ustadz): berapa santri hadir, berapa tidak hadir, dan berapa **kelas yang belum diabsen** hari ini. Angka ketiga itu yang paling berguna untuk memantau — ia menunjukkan kelas mana yang ustadznya belum mengisi. Kalau hari ini terdaftar libur, kartunya berganti jadi keterangan liburnya, karena "belum diabsen" saat libur memang bukan kelalaian.

> Catatan: rekap hanya mencakup **santri aktif**. Santri yang sudah dihapus dari sistem tidak muncul di rekap meski catatan kehadirannya masih tersimpan.

Presensi juga bisa disertakan di **Rapor** (bagian 9) sebagai salah satu modul yang dicentang, sehingga ikut tercetak di PDF rapor santri.

---

## 9. Rapor

Menu: **Rapor** — **Admin & Ustadz**

Cluster ini berisi 4 halaman untuk melihat & mencetak rekap per santri per periode — semuanya sudah dihitung otomatis dari data yang diinput di modul masing-masing (tidak perlu rekap manual):

| Halaman | Isi | Sumber data |
|---|---|---|
| **Akademik** | Rata-rata nilai per mapel, daftar ekskul aktif | Modul Nilai Akademik & Ekskul |
| **Tahfidz** | Total setoran, total juz tercapai, hasil ujian | Modul Setoran & Ujian Tahfidz |
| **Mutabaah** | Rekap persentase capaian tiap amalan sebulan | Modul Mutaba'ah |
| **Karakter** | Nilai adab & kepribadian per periode | Modul Karakter |

**Cara pakai (sama di keempat halaman):** pilih Santri dari dropdown, pilih Tahun Ajaran dan Periode (Bulanan/Semester — pilih Bulan kalau Bulanan), lalu data akan tampil otomatis. Klik **Unduh PDF** di kanan atas untuk mencetak/mengunduh rapor tersebut. Kalau belum ada data untuk kombinasi santri+periode yang dipilih, sistem akan memberi tahu "Belum ada data" alih-alih mencetak rapor kosong.

Ustadz hanya bisa memilih santri bimbingannya sendiri di dropdown Santri.

---

## 10. Keuangan

Menu: **Keuangan** — **Admin saja** (ustadz tidak punya akses sama sekali ke modul ini)

### 10.1 Tarif SPP

Menu: **Keuangan → Tarif SPP**. Tentukan nominal SPP bulanan per kelas.

1. Klik **+ New**. Pilih Kelas, isi Nominal SPP (Rp), dan Keterangan (opsional).
2. Simpan. Nominal ini akan dipakai sebagai acuan saat generate tagihan.

### 10.2 Tagihan SPP

Menu: **Keuangan → Tagihan SPP**.

**Membuat tagihan bulanan untuk semua santri sekaligus:**
1. Klik **Generate Tagihan Massal** di atas tabel.
2. Pilih Bulan dan Tahun, isi Jatuh Tempo (opsional) dan Keterangan (default "SPP Bulanan").
3. Klik proses — sistem otomatis membuat tagihan untuk semua santri aktif berdasarkan tarif kelas masing-masing. Santri yang kelasnya belum punya Tarif SPP, atau yang tagihan bulan itu sudah pernah dibuat, akan dilewati (jumlahnya dilaporkan di notifikasi).

**Menandai tagihan lunas:**
- Klik tombol **Tandai Lunas** pada baris tagihan, isi Tanggal Bayar, Metode Bayar (tunai/transfer/dll), dan Catatan opsional, lalu simpan.
- Tagihan yang punya bukti transfer dari wali (status "Menunggu Konfirmasi") ditandai dengan badge **!** — cek bukti transfernya di halaman Detail sebelum menandai lunas.

**Menolak konfirmasi transfer:** kalau bukti transfer yang diunggah wali ternyata tidak valid, klik **Tolak** — status tagihan kembali ke "Belum Bayar" dan bukti transfer yang salah dihapus dari sistem.

Filter tabel tersedia berdasarkan Status, Bulan, dan Tahun.

### 10.3 Uang Saku Santri

Menu: **Keuangan → Uang Saku**. Ledger setoran/pengambilan uang saku per santri.

1. Klik **+ New**. Pilih Santri, Jenis Transaksi (Setoran/Pengambilan), Nominal (Rp), Tanggal, dan Keterangan (opsional).
2. Simpan.

**Melihat rekap saldo semua santri:** menu **Keuangan → Saldo Santri** menampilkan tabel saldo (total setoran − total pengambilan) per santri, bisa dicari berdasarkan nama.

---

## 11. Pengumuman

Menu: **Pengumuman** (grup Manajemen)

**Siapa yang bisa akses:** Admin (buat, ubah, hapus — untuk pesantrennya sendiri) · Ustadz (hanya bisa melihat)

1. Klik **+ New**. Isi Judul Pengumuman, Isi Pengumuman (editor teks kaya — bisa bold, italic, list, heading).
2. Pilih **Tampilkan Kepada**: Semua Pengguna, Admin & Ustadz Pesantren saja, atau Wali Santri saja.
3. Simpan. Pengumuman langsung tampil ke audiens yang dipilih (termasuk muncul di feed portal wali dan dashboard).

---

## 12. Magic Link — Akses Portal Wali

**Siapa yang bisa akses:** tombol "Link Wali" tersedia untuk Admin & Ustadz; tombol "Regenerasi Link" khusus Admin.

Wali santri tidak login dengan email/password — mereka mengakses laporan anaknya lewat **link unik tanpa password** (Magic Link).

**Cara memberikan akses ke wali:**
1. Buka menu **Santri → Santri**, cari santri yang dimaksud.
2. Pastikan santri tersebut sudah terhubung ke akun **Wali Santri** (lihat [bagian 2.1](#21-data-santri), bagian Relasi) — kalau belum, tombol Link Wali akan nonaktif dengan keterangan untuk menghubungkan dulu lewat Edit.
3. Klik tombol **Link Wali** pada baris santri tersebut. Modal akan menampilkan URL unik (`app.walisantri.com/report/{kode-unik}`).
4. Salin/kirim link tersebut ke wali santri (lewat WhatsApp, dsb). Wali cukup membuka link ini di browser untuk langsung masuk ke portal laporan anaknya, tanpa perlu login.

**Kapan perlu Regenerasi Link (Admin saja):** kalau link lama diduga bocor ke pihak yang tidak berhak, klik **Regenerasi Link** pada baris santri — link lama langsung tidak berlaku dan Anda perlu mengirim link baru ke wali.

---

## 13. Preview Portal Wali

**Siapa yang bisa akses:** Admin & Ustadz

Kadang Anda perlu mengecek bagaimana tampilan laporan santri dari sudut pandang wali — tanpa harus punya Magic Link atau logout dari akun Anda. Klik tombol **Preview sebagai Wali** pada baris santri di menu Santri — halaman laporan akan terbuka di tab baru persis seperti yang dilihat wali santri (read-only, tidak bisa mengubah data dari sini).

Berguna untuk QA sebelum mengirim link ke wali, atau untuk membantu wali yang bingung lewat telepon ("coba lihat di layar saya, seharusnya tampilannya begini...").

---

## 14. Pengaturan Pesantren & Langganan

Menu: **Pengaturan** (grup Manajemen) — **Admin saja**

### 14.1 Pengaturan Profil Pesantren

Menu: **Pengaturan → Pengaturan**. Kelola identitas dan profil publik pesantren (tampil di halaman profil publik `{slug}.walisantri.com`):

- **Identitas Pesantren**: Nama Pesantren, Subdomain (slug URL publik). Mengubah slug akan melepas slug lama ke masa tunggu 90 hari sebelum bisa dipakai pesantren lain.
- **Logo & Galeri**: unggah logo (PNG/JPG/SVG, maks 1 MB) dan galeri foto (maks 12 foto, bisa diurutkan ulang).
- **Profil Publik**: Nomor Telepon, Alamat, Deskripsi Singkat.
- **Program & Jenjang Pendidikan**: tambahkan daftar program (contoh: "Tahfidz Al-Qur'an" — "Setingkat SMP/SMA") yang tampil di profil publik.
- **Statistik Ringkas**: Tahun Berdiri, Akreditasi (jumlah santri dihitung otomatis, tidak perlu diisi manual).
- **Rekening Pembayaran SPP**: daftar rekening bank yang ditampilkan ke wali santri saat mereka melihat tagihan SPP — bisa lebih dari satu rekening.

Klik **Simpan Perubahan** di bagian bawah setelah selesai mengubah.

### 14.2 Billing (Informasi Langganan)

Menu: **Pengaturan → Billing**. Menampilkan status langganan pesantren saat ini (paket, kuota santri, tanggal berakhir) dan detail order aktif kalau sedang dalam proses upgrade. Tombol **Upgrade / Perpanjang Paket** membawa Anda ke halaman Upgrade.

### 14.3 Upgrade / Perpanjang Paket

1. Dari halaman Billing, klik **Upgrade / Perpanjang Paket**.
2. Pilih **Paket Tujuan**. Untuk paket Maju, atur juga **Kuota Santri** (minimum 1.000, kelipatan 100).
3. Pilih **Durasi Langganan**. Catatan: kalau sisa masa aktif langganan Anda saat ini masih panjang (lebih dari 6 atau 9 bulan), sistem mewajibkan durasi perpanjangan minimum lebih lama (6 atau 12 bulan) — ini otomatis dijelaskan di halaman.
4. Masukkan **Kode Kupon** kalau ada, klik Terapkan untuk memvalidasi diskon.
5. Klik **Lakukan Pembayaran** — Anda akan diarahkan ke halaman Invoice.
6. Di halaman Invoice, lihat daftar rekening bank platform yang tersedia, transfer sesuai nominal, lalu **unggah bukti transfer** (JPG/PNG/PDF, maks 5 MB) dan klik **Kirim Bukti Transfer**.
7. Tim platform akan memverifikasi dalam waktu 1×24 jam. Setelah dikonfirmasi, paket baru otomatis aktif.

> Menurunkan paket (downgrade) tidak menghapus data — modul yang tidak tersedia di paket baru hanya dikunci sementara, datanya tetap tersimpan dan bisa diakses lagi kalau upgrade ulang.

---

## 15. Troubleshooting Umum

**"Langganan pesantren telah berakhir" / diarahkan paksa ke halaman Billing.**
Paket langganan pesantren sudah kadaluwarsa. Hanya Admin Pesantren yang bisa membuka halaman Billing untuk memperpanjang — kalau Anda login sebagai Ustadz dan mendapat pesan ini, hubungi admin pesantren Anda untuk memperpanjang langganan.

**"Batas kuota paket tercapai!" saat menambah/mengimpor santri.**
Jumlah santri aktif sudah mencapai batas maksimal paket langganan saat ini. Nonaktifkan santri yang sudah tidak aktif (bukan dihapus) untuk melepas kuota, atau upgrade paket lewat menu Billing.

**"Ustadz ini sudah mencapai batas maksimal 20 santri."**
Satu ustadz maksimal membimbing 20 santri aktif sekaligus. Pilih ustadz lain sebagai pembimbing, atau kurangi jumlah santri bimbingan ustadz tersebut terlebih dahulu.

**Menu tertentu (misal Inventaris) tidak muncul di sidebar.**
Beberapa modul hanya tersedia di paket langganan tertentu (contoh: Inventaris khusus paket Maju). Cek halaman Billing untuk melihat paket aktif pesantren Anda, atau upgrade paket kalau modul tersebut dibutuhkan.

**Tombol "Link Wali" nonaktif/abu-abu.**
Santri tersebut belum terhubung ke akun Wali Santri. Buka Edit Santri → bagian Relasi → pilih Wali Santri-nya, simpan, baru tombol Link Wali bisa dipakai.

**Wali santri melapor tidak bisa membuka link laporan.**
Kemungkinan link sudah pernah di-regenerasi (link lama otomatis tidak berlaku begitu link baru dibuat). Buka menu Santri, klik **Link Wali** untuk mendapatkan link terbaru, lalu kirim ulang ke wali.

**Ustadz tidak bisa melihat/mengubah data seorang santri.**
Cek apakah santri tersebut sudah diset sebagai bimbingan ustadz itu (`Ustadz Pembimbing` di form Santri) — ustadz hanya bisa mengelola data santri bimbingannya sendiri di sebagian besar modul.

---

## 16. Lampiran: Apa yang Dilihat Wali Santri

Wali santri mengakses portal terpisah lewat Magic Link, tampilannya read-only (tanpa bisa mengubah data), berisi:

- **Beranda**: sapaan, ringkasan singkat, alert kalau anaknya sedang sakit atau ada tunggakan SPP
- **Detail Santri**: biodata, capaian tahfidz, statistik kesehatan, prestasi, ekstrakurikuler aktif
- **Mutaba'ah**: tabel amalan harian anaknya dengan filter tanggal
- **Presensi**: rekap kehadiran bulan berjalan (Hadir, Sakit, Izin, Alpa, Terlambat, Pulang, Dispensasi), jumlah hari efektif, persentase kehadiran, dan daftar hariannya
- **Pengajuan Izin**: form mengajukan izin untuk anaknya (jenis, rentang tanggal, alasan, lampiran surat dokter) beserta status tiap pengajuan
- **Rapor**: tab Tahfidz, Akademik, Karakter, Mutabaah per periode + tombol unduh PDF
- **SPP**: daftar tagihan, info rekening bank pesantren, tombol "Saya Sudah Transfer" untuk mengunggah bukti pembayaran
- **Uang Saku**: saldo dan riwayat setoran/pengambilan
- **Inventaris**: daftar barang titipan anaknya (kalau paket Maju)
- **Pengumuman**: daftar pengumuman yang ditujukan ke wali/semua pengguna

Bottom navigation di portal wali: **Beranda · SPP · Pengumuman · Uang Saku · Rapor**. Presensi dan Pengajuan Izin dicapai dari halaman detail santri, bukan dari bottom navigation.

> ⚠️ **Wali yang masuk lewat Magic Link tidak bisa mengajukan izin.** Sesi Magic Link sengaja dibuat baca-saja demi keamanan. Untuk mengajukan izin, wali harus masuk lewat halaman login dengan akunnya sendiri — form pengajuan otomatis diganti keterangan ini kalau wali datang dari Magic Link. Sampaikan ini bila ada wali yang bertanya kenapa tombolnya tidak muncul.

Memahami tampilan ini membantu Anda menjelaskan ke wali santri apa yang akan mereka lihat setelah Anda menginput data — atau gunakan [Preview sebagai Wali](#13-preview-portal-wali) untuk melihat langsung dari akun Anda sendiri.
