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
| `RCLONE_REMOTE` | *(kosong)* | Remote offsite, mis. `b2crypt:walisantri-backup`. Kosong = offsite dilewati |
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

### 2. Konfigurasi remote offsite (rekomendasi: Backblaze B2)

Buat akun Backblaze B2, lalu buat **bucket privat** `walisantri-backup` + Application Key.

```bash
rclone config
#  n) new remote  → nama: b2
#  Storage: Backblaze B2  → isi account ID & application key
```

**Lebih aman (opsional): enkripsi at-rest** — bungkus dengan remote `crypt`
sehingga artefak (termasuk `.env`) terenkripsi di cloud:

```bash
rclone config
#  n) new remote  → nama: b2crypt
#  Storage: crypt
#  remote: b2:walisantri-backup
#  (simpan password crypt di tempat aman — TANPA ini, backup tidak bisa dibuka)
```

Uji koneksi:

```bash
export RCLONE_REMOTE="b2crypt:walisantri-backup"   # atau "b2:walisantri-backup" tanpa enkripsi
rclone lsd "${RCLONE_REMOTE%%:*}:"
```

### 3. Uji backup manual

```bash
cd /var/www/walisantri
RCLONE_REMOTE="b2crypt:walisantri-backup" bash scripts/backup.sh
ls -lh /home/fazaweb/backups/walisantri            # cek artefak lokal
rclone ls b2crypt:walisantri-backup                # cek artefak offsite
```

### 4. Pasang cron harian (02:00)

`crontab -e` (user `fazaweb`), tambahkan:

```cron
MAILTO="kantorpusatnpc@gmail.com"
RCLONE_REMOTE=b2crypt:walisantri-backup
0 2 * * * /var/www/walisantri/scripts/backup.sh >> /home/fazaweb/backups/walisantri.log 2>&1
```

Cron ini independen dari `schedule:run` Laravel — lebih sederhana & andal.

---

## Cara restore (mudah & aman)

> **Selalu uji restore di STAGING dulu**, jangan langsung di production.
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
export RCLONE_REMOTE="b2crypt:walisantri-backup"
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

## Uji-restore berkala di staging (disarankan tiap bulan)

```bash
# di server staging 116.206.196.37
cd /var/www/walisantri
export RCLONE_REMOTE="b2crypt:walisantri-backup"
bash scripts/restore.sh --from-offsite latest
# lalu buka https://staging.walisantri.com, pastikan login & data tampil normal
```

Backup yang tidak pernah diuji-restore = belum tentu bisa dipakai. Jadikan ini rutin.

---

## Dump pra-deploy otomatis

`.github/workflows/deploy.yml` menjalankan `scripts/backup.sh --db-only --no-offsite
--tag pre-deploy` **sebelum** `php artisan migrate --force` di setiap deploy. Jadi
setiap perubahan skema punya titik rollback lokal (`db-<ts>-pre-deploy.dump`).
