# Backup & Restore Walisantri

Panduan sederhana untuk mem-backup dan me-restore Walisantri dengan aman.
Pendekatan: **skrip bash + cron + salinan offsite via rclone**. Tidak ada
dependensi aplikasi baru; restore dirancang aman (snapshot pengaman + konfirmasi).

## Apa yang di-backup

Walisantri memakai **satu** database Postgres (`walisantri_db`, schema `public`,
single-database tenancy — semua pesantren dalam satu DB). Backup lengkap mencakup:

1. **Database** — `pg_dump -Fc` (custom format, terkompresi).
2. **File upload** — `storage/app/public` & `storage/app/private` (foto santri,
   bukti transfer SPP, logo/galeri pesantren, dokumen prestasi).
3. **`.env`** — berisi `APP_KEY` & rahasia lain (wajib, agar data terenkripsi di
   DB tetap terbaca setelah restore).

Setiap artefak disertai checksum `SHA256SUMS-<timestamp>` untuk cek integritas.

## Skrip

| Skrip | Fungsi |
|-------|--------|
| `scripts/backup.sh`  | Backup DB + file + .env, checksum, rotasi lokal, unggah offsite |
| `scripts/restore.sh` | Restore aman: snapshot pengaman → verifikasi → konfirmasi → restore |

Konfigurasi lewat environment (punya default production):

| Env | Default | Keterangan |
|-----|---------|------------|
| `APP_DIR` | `/var/www/walisantri` | Lokasi aplikasi |
| `BACKUP_DIR` | `/home/fazaweb/backups/walisantri` | Simpanan lokal (dimiliki user, tanpa sudo) |
| `RCLONE_REMOTE` | *(kosong)* | Remote offsite, mis. `odcrypt:walisantri-backup`. Kosong = offsite dilewati |
| `LOCAL_RETENTION_DAYS` | `7` | Retensi lokal |
| `OFFSITE_RETENTION_DAYS` | `30` | Retensi offsite |

Kredensial DB dibaca otomatis dari `$APP_DIR/.env` (`DB_*`), jadi tidak ada
password yang ditulis di skrip.

---

## Setup satu kali di production (server `157.20.159.70`)

Jalankan sebagai user `fazaweb`.

### 1. Install rclone

```bash
sudo apt update && sudo apt install -y rclone   # atau: curl https://rclone.org/install.sh | sudo bash
```

### 2. Konfigurasi remote offsite (OneDrive)

OneDrive punya backend rclone `onedrive`. Karena server headless (tanpa
browser), OAuth dilakukan di komputer lokal lalu tokennya dipindah.

**a. Buat remote `onedrive` di server:**

```bash
rclone config
#  n) new remote  → nama: onedrive
#  Storage: onedrive (Microsoft OneDrive)
#  client_id / client_secret: kosongkan (Enter)
#  Edit advanced config? n
#  Use auto config? →  N  (server headless, tidak ada browser)
#  rclone akan menampilkan perintah:  rclone authorize "onedrive"
```

**b. Di komputer LOKAL (punya browser) yang sudah ada rclone**, jalankan
perintah yang ditampilkan tadi, login Microsoft, lalu salin token JSON yang
muncul dan tempel kembali ke prompt di server. Pilih drive OneDrive pribadi
(100 GB) saat ditanya.

**Lebih aman (opsional): enkripsi at-rest** — bungkus dengan remote `crypt`
sehingga artefak (termasuk `.env`) terenkripsi di OneDrive:

```bash
rclone config
#  n) new remote  → nama: odcrypt
#  Storage: crypt
#  remote: onedrive:walisantri-backup
#  (simpan password crypt di tempat aman — TANPA ini, backup tidak bisa dibuka)
```

Uji koneksi (folder `walisantri-backup` dibuat otomatis saat unggah pertama):

```bash
export RCLONE_REMOTE="odcrypt:walisantri-backup"   # atau "onedrive:walisantri-backup" tanpa enkripsi
rclone lsd "${RCLONE_REMOTE%%:*}:"
```

### 3. Uji backup manual

```bash
cd /var/www/walisantri
RCLONE_REMOTE="odcrypt:walisantri-backup" bash scripts/backup.sh
ls -lh /home/fazaweb/backups/walisantri            # cek artefak lokal
rclone ls odcrypt:walisantri-backup                # cek artefak offsite
```

### 4. Pasang cron harian (02:00)

`crontab -e` (user `fazaweb`), tambahkan:

```cron
MAILTO="kantorpusatnpc@gmail.com"
RCLONE_REMOTE=odcrypt:walisantri-backup
0 2 * * * /var/www/walisantri/scripts/backup.sh >> /home/fazaweb/backups/walisantri.log
```

Cron ini independen dari `schedule:run` Laravel — lebih sederhana & andal.

> ⚠️ **Perhatikan tidak ada `2>&1`, dan itu disengaja.** stdout (jejak langkah)
> masuk berkas log; stderr (baris `WARN`/`ERROR`) dibiarkan mengalir ke cron
> supaya dikirim ke `MAILTO`. Versi lama baris ini memakai `2>&1`, sehingga cron
> tidak pernah melihat keluaran apa pun dan tidak pernah mengirim surel.
> Akibatnya nyata: **15–29 Agustus 2026 tidak ada satu pun backup offsite** dan
> tidak ada yang tahu. Pemicunya PHP-FPM (`www-data`) membuat
> `storage/app/private/bukti-transfer/<id>` dengan mode 0700 sementara backup
> berjalan sebagai `fazaweb`; `tar` gagal, dan saat itu kegagalan `tar` bersifat
> fatal sehingga skrip berhenti **sebelum** langkah unggah offsite — dump
> database yang sudah jadi pun ikut tidak pernah keluar dari server.
>
> Tiga hal ditutup sekaligus (v4.59): `config/filesystems.php` kini menyetel
> disk `local` ke 0750/0640 supaya direktori baru terbaca grup `www-data`;
> `scripts/backup.sh` tidak lagi `die` saat `tar` gagal (arsipnya diberi nama
> `-PARSIAL`, offsite tetap jalan, skrip keluar dengan status 1); dan baris cron
> ini melepas `2>&1`. Prasyarat di server: `usermod -aG www-data fazaweb`.

---

## Cara restore (mudah & aman)

> **Latihan restore selalu di laptop, jangan di production** (lihat "Uji-restore
> berkala" di bawah). Restore di production hanya untuk pemulihan sungguhan.
> Skrip **otomatis membuat snapshot pengaman** kondisi saat ini sebelum menimpa,
> jadi restore yang salah tetap bisa dibalik.

### Lihat backup yang tersedia

```bash
cd /var/www/walisantri
bash scripts/restore.sh --list
```

### Restore dari backup lokal

```bash
bash scripts/restore.sh latest              # atau timestamp: 20260725-020000
```

Alur yang dijalankan skrip:
1. Verifikasi checksum artefak.
2. Buat snapshot pengaman (`pre-restore-<ts>/` di `BACKUP_DIR`).
3. Minta konfirmasi (ketik nama database).
4. `php artisan down` + stop worker.
5. `pg_restore --clean --if-exists` + ekstrak file.
6. Segarkan cache, start worker, `php artisan up`.
7. Sanity check (`migrate:status`, hitung baris `pesantrens`/`users`).

### Restore dari offsite (mis. server hilang total)

```bash
export RCLONE_REMOTE="odcrypt:walisantri-backup"
bash scripts/restore.sh --from-offsite latest
```

### Membatalkan hasil restore

Skrip mencetak lokasi snapshot pengaman. Untuk kembali ke kondisi sebelum restore:

```bash
pg_restore --clean --if-exists --no-owner -d walisantri_db \
  /home/fazaweb/backups/walisantri/pre-restore-<ts>/db-current.dump
tar xzf /home/fazaweb/backups/walisantri/pre-restore-<ts>/files-current.tar.gz -C /var/www/walisantri
```

---

## Uji-restore berkala di laptop (disarankan tiap bulan)

Sejak staging dibubarkan (lihat PRD §18), uji-restore dilakukan di mesin lokal —
**jangan** pernah menjalankannya di server production untuk sekadar latihan.
Justru ini lebih dekat ke skenario sesungguhnya: "server hilang total, pulihkan
dari nol di mesin lain".

Syarat sekali-pasang di laptop: `rclone` versi baru (lihat gotcha di bawah),
remote `odcrypt` (butuh passphrase yang disimpan di password manager), serta
Postgres lokal (DBngin).

```bash
# 1. Salinan repo TERPISAH dari direktori kerja sehari-hari,
#    supaya .env-nya tidak tertukar dengan .env development.
git clone https://github.com/fazaid/walisantri.git ~/restoretest
cd ~/restoretest
cp .env.example .env

# 2. Arahkan ke DB uji lokal — restore.sh membaca target dari .env ini.
#    Buat dulu database kosongnya: createdb walisantri_restoretest
#    Isi DB_DATABASE=walisantri_restoretest, DB_HOST=127.0.0.1, DB_USERNAME/DB_PASSWORD lokal.

# 3. Tarik & pulihkan backup terenkripsi terakhir.
export APP_DIR="$HOME/restoretest"
export BACKUP_DIR="$HOME/restoretest/backups"
export RCLONE_REMOTE="odcrypt:walisantri-backup"
bash scripts/restore.sh --from-offsite latest
```

Verifikasi setelah selesai: jumlah baris `pesantrens`/`santris`/`users` masuk
akal, dan `php artisan tinker` bisa membaca satu record. Untuk membuka aplikasinya
di browser, pakai `APP_KEY` dari `.env` yang ikut ter-backup — tanpa itu isi
`whatsapp_gateway_settings` (satu-satunya kolom terenkripsi) tidak terbaca.

Backup yang tidak pernah diuji-restore = belum tentu bisa dipakai. Jadikan ini
rutin — sekarang statusnya **naik jadi penting**, karena tidak ada lagi
environment kedua yang bisa dipakai membuktikan pemulihan berjalan.

---

## Dump pra-deploy otomatis

`.github/workflows/deploy.yml` menjalankan `scripts/backup.sh --db-only --no-offsite
--tag pre-deploy` **sebelum** `php artisan migrate --force` di setiap deploy. Jadi
setiap perubahan skema punya titik rollback lokal (`db-<ts>-pre-deploy.dump`).
