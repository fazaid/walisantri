# Prompt Fable — Presentasi Promosi Walisantri untuk Pesantren

Prompt siap pakai untuk model **Fable** (image/design generation) — satu **Style Guide** (tempel di awal setiap prompt slide supaya gayanya konsisten) lalu **14 prompt per-slide**. Semua headline/angka diambil langsung dari landing page & PRD Walisantri.

## Catatan

- **Revisi 2026-07-03:** ke-14 slide di bawah sudah disinkronkan ke narasi landing page terbaru — wali santri jadi persona utama (bukan admin/pengurus), Portal Wali Santri & Profil Publik Pesantren dinaikkan urutannya jadi slide 4-5 (langsung setelah Solusi & Visi), slide "Dashboard Admin" diganti "Dashboard Ustadz" mengikuti menu sidebar ustadz sungguhan, dan urutan angka traksi di slide 13 memprioritaskan jumlah wali terdaftar. Slide Paket Harga & Traksi tetap di posisi 12 & 13 (nomornya tidak berubah).
- Angka di slide 13 (10+ pesantren, 298+ wali) adalah angka riil yang dipakai di landing page saat file ini ditulis — cek dan update dulu sebelum presentasi kalau sudah berubah.
- Slide 12 sengaja memakai harga normal apa adanya, meskipun produk masih di fase beta gratis — kalau berubah pikiran, tambahkan kalimat "Gratis selama masa beta" di prompt itu.
- Kalau hasil render Fable kurang pas di gaya tertentu, paling efektif menambah/mengurangi kata sifat gaya di Style Guide (mis. "lebih minimalis", "motif lebih terlihat") — bukan mengubah tiap prompt slide satu-satu.

---

## 🎨 Style Guide (sertakan di setiap prompt slide)

```
Desain slide presentasi bisnis B2B SaaS, format landscape 16:9, gaya "Islami-modern".
Palet warna: hijau teal tua #0F766E sebagai warna utama, teal gelap #115E59 untuk kontras,
latar hijau sangat muda #F0FDFA, aksen emas antik #C9A227 untuk garis/ikon/highlight,
teks gelap #1F2937 di atas latar terang, putih gading #FAFAF7 sebagai netral.
Tipografi: sans-serif modern-geometris yang bersih dan mudah dibaca (mirip Inter/Instrument Sans),
headline tebal, body text reguler, hierarki jelas.
Motif dekoratif: pola geometris Islami halus (bintang delapan/girih) dipakai tipis sebagai
elemen border, watermark, atau aksen sudut — jangan dominan, jangan ramai.
Mood: profesional, hangat, terpercaya, elegan — bukan playful/kartunis, bukan norak.
Layout: banyak white space, ikon line-art minimalis satu warna, grid rapi, tidak padat teks.
Setiap slide harus terlihat sebagai bagian dari satu rangkaian deck yang konsisten.
```

---

## 📑 14 Prompt Slide

### 1. Cover

```
[Style Guide] + Slide cover/pembuka presentasi.
Elemen: logo teks "Walisantri.com" (ikon masjid minimalis line-art + wordmark) di tengah atas,
headline besar: "Wali Santri Pantau Anaknya, Pesantren Makin Transparan",
sub-teks kecil: "Satu Platform yang Menghubungkan Pesantren & Wali Santri".
Background: gradasi hijau teal ke putih, motif geometris Islami halus di sudut kanan bawah.
Area kosong di bawah untuk nama audiens/tanggal presentasi.
```

### 2. Masalah

```
[Style Guide] + Slide "Tantangan Pesantren Saat Ini".
Headline: "Mengelola Pesantren Masih Manual dan Melelahkan?"
3 poin masalah dengan ikon line-art masing-masing:
- "Wali santri sulit memantau perkembangan anak dari jauh"
- "Rapor & data santri masih tercecer di kertas dan Excel"
- "Admin kewalahan urus SPP, kesehatan, tahfidz, akademik secara terpisah"
Layout: 3 kolom/kartu sejajar, ikon di atas tiap kartu, teks singkat di bawahnya.
```

### 3. Solusi & Visi

```
[Style Guide] + Slide "Perkenalkan: Walisantri".
Headline: "Standar Digitalisasi Pesantren Indonesia"
3 pilar berdampingan dengan ikon berbeda:
- "Terlengkap" — satu platform untuk akademik, pengasuhan, kesehatan, keuangan, komunikasi
- "Terjangkau" — mulai dari Rp150.000/bulan
- "Dipercaya" — isolasi data per pesantren & audit log keamanan
Layout: 3 pilar simetris dengan garis emas tipis pemisah antar kolom.
```

### 4. Portal Wali Santri

```
[Style Guide] + Slide fitur unggulan "Portal Wali Santri".
Headline: "Wali Santri Pantau Anak Cukup dari WhatsApp"
Bullet: "Login tanpa install aplikasi — cukup Magic Link via WhatsApp",
"Lihat tahfidz, nilai, kesehatan, SPP, dan pengumuman dalam satu layar",
"Akses kapan saja dari HP masing-masing".
Visual: mockup layar smartphone menampilkan dashboard wali santri sederhana,
dengan ikon WhatsApp kecil sebagai aksen (line-art, bukan logo resmi).
```

### 5. Profil Publik Pesantren

```
[Style Guide] + Slide fitur "Profil Publik Pesantren".
Headline: "Pesantren Anda Punya Website Sendiri, Otomatis"
Bullet: "Situs profil aktif otomatis di alamat namapesantren.walisantri.com",
"Tampilkan logo, galeri, dan statistik pesantren ke publik",
"Tanpa perlu sewa hosting atau developer terpisah".
Visual: mockup tampilan browser/website sederhana dengan header hijau teal dan logo placeholder.
```

### 6. Cara Kerja

```
[Style Guide] + Slide "Mulai dalam 3 Langkah Mudah".
Alur horizontal 3 langkah dengan panah penghubung bergaya garis emas:
1. "Daftar" — isi data pesantren
2. "Setup 3 Menit" — atur kelas, ustadz, santri
3. "Pakai" — wali, ustadz, dan admin langsung aktif
Ikon line-art sederhana untuk tiap langkah.
```

### 7. Modul Tahfidz Al-Quran

```
[Style Guide] + Slide fitur "Tahfidz Al-Quran".
Headline: "Pantau Hafalan Santri Secara Digital"
Bullet: "Catat setoran & murajaah harian", "Progress otomatis per juz/halaman",
"Rapor tahfidz PDF siap cetak".
Visual: mockup layar/kartu UI sederhana menampilkan progress bar hafalan santri,
dikombinasikan dengan ilustrasi mushaf/Al-Quran minimalis bergaya line-art.
```

### 8. Modul Akademik & Rapor

```
[Style Guide] + Slide fitur "Akademik & Rapor".
Headline: "Nilai dan Rapor Rapi Tanpa Ribet"
Bullet: "Kelola kelas & mata pelajaran", "Input nilai per bulan/semester",
"Rapor Akademik, Tahfidz, Mutaba'ah, dan Karakter jadi satu PDF terintegrasi".
Visual: mockup dokumen rapor PDF minimalis dengan header hijau-emas, di atas latar kartu UI.
```

### 9. Modul Kesantrian

```
[Style Guide] + Slide fitur "Kesantrian".
Headline: "Pantau Ibadah, Kesehatan, dan Karakter Santri"
4 sub-modul dalam grid kartu kecil dengan ikon masing-masing:
"Mutaba'ah Harian" (ibadah), "Kesehatan Santri" (rekam medis), "Karakter" (rapor sikap),
"Inventaris" (barang santri).
```

### 10. Modul Keuangan

```
[Style Guide] + Slide fitur "Keuangan".
Headline: "Kelola SPP dan Uang Saku Tanpa Kertas"
Bullet: "Tagihan SPP bulanan otomatis", "Konfirmasi transfer wali santri langsung dari HP",
"Catatan Uang Saku Santri real-time".
Visual: ikon dompet/uang line-art bergaya emas, mockup kartu tagihan sederhana.
```

### 11. Dashboard Ustadz & Multi-Role

```
[Style Guide] + Slide "Satu Panel untuk Semua Peran".
Headline: "Wali Santri, Ustadz, dan Admin dalam Satu Sistem"
Visual: diagram 3 peran (urutan kiri ke kanan: Wali Santri, Ustadz, Admin Pesantren)
terhubung ke satu ikon platform/panel di tengah, masing-masing dengan ikon peran
line-art sederhana. Di belakang diagram, sisipkan mockup layar "Dashboard Ustadz" secara
samar (watermark-style): browser bar bertuliskan "app.walisantri.com/admin", sidebar berisi
menu "Dashboard, Santri, Akademik, Tahfidz, Mutaba'ah, Kesantrian, Rapor".
Teks singkat di bawah tiap peran menjelaskan akses masing-masing.
```

### 12. Paket Harga

```
[Style Guide] + Slide "Paket Harga".
Headline: "Pilih Paket Sesuai Kebutuhan Pesantren Anda"
4 kartu harga sejajar:
- "Rintisan — Rp150.000/bulan"
- "Tumbuh — Rp299.000/bulan" (beri badge emas "Paling Populer")
- "Berkembang — Rp350.000/bulan"
- "Maju — Rp750.000/bulan"
Tambahkan teks kecil di bawah: "Coba gratis 30 hari, tanpa kartu kredit".
Layout: 4 kartu vertikal sejajar, kartu "Tumbuh" sedikit lebih menonjol/tinggi.
```

### 13. Traksi & Testimoni

```
[Style Guide] + Slide "Dipercaya Pesantren di Seluruh Indonesia".
4 angka statistik besar dengan label di bawahnya, disusun dalam grid 2x2 atau 1 baris:
"298+ Wali Terdaftar", "10+ Pesantren Bergabung", "8+ Modul Lengkap", "3 Menit Setup Awal".
Di bawahnya, sisakan area kosong bergaya kartu kutipan untuk testimoni — satu dari wali
santri, satu dari pengasuh/ustadz pesantren (tanda kutip besar bergaya emas, placeholder
untuk nama & foto).
```

### 14. CTA & Kontak

```
[Style Guide] + Slide penutup/CTA.
Headline besar: "Saatnya Pesantren Anda Go Digital"
Sub-teks: "Coba Walisantri gratis 30 hari — wali santri Anda mulai terhubung sejak hari pertama"
Tombol besar bergaya (visual, bukan interaktif) bertuliskan "Mulai Sekarang" dengan warna
emas menonjol di atas latar hijau teal gelap.
Area kontak di bawah untuk logo, nomor WhatsApp, dan website (placeholder teks).
```
