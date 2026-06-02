# Walisantri.com

B2B Multi-Tenant SaaS untuk manajemen pesantren — menghubungkan admin pesantren, ustadz, dan wali santri dalam satu platform.

**Stack:** Laravel 13 · Filament v5 · PostgreSQL 17 · Livewire v3 · TailwindCSS · Redis · Cloudflare R2

---

## Fitur Utama

| Modul | Deskripsi | Paket |
|---|---|---|
| Portal Wali Santri | Pantau perkembangan santri via web & Magic Link WhatsApp | Semua |
| Profil Publik Pesantren | Situs `{slug}.walisantri.com` otomatis aktif saat registrasi | Semua |
| Tahfidz | Progress hafalan, ujian, rapor tahfidz | Semua |
| Mutaba'ah Yaumiyah | Input harian ibadah santri | Semua |
| Pengumuman | Pengumuman per pesantren & siaran sentral | Semua |
| Kesehatan Santri | Rekam medis & pemantauan kondisi | Berkembang+ |
| Inventaris | Manajemen barang santri | Maju |
| Dashboard Central | Monitoring semua tenant (Super Admin) | — |

---

## Arsitektur Domain

```
walisantri.com              → Landing page + registrasi pesantren baru
{slug}.walisantri.com       → Profil publik pesantren (read-only)
app.walisantri.com/login    → Login terpusat semua role
app.walisantri.com/admin    → Panel Filament (Super Admin / Admin / Ustadz)
app.walisantri.com/wali     → Portal Wali Santri
```

Tenant di-resolve dari **akun** (email unik global → `pesantren_id`), bukan dari host. Satu panel Filament melayani semua role — menu difilter via `canAccess()` / `canView()` per role.

---

## Paket Langganan

| Paket | Harga/bulan | Kuota Santri |
|---|---|---|
| Gratis | Rp 0 | ≤ 10 |
| Rintisan | Rp 150.000 | ≤ 100 |
| Berkembang | Rp 450.000 | ≤ 500 |
| Maju | Rp 750.000 | ≤ 1.000 |

Kuota custom di atas 1.000 dihitung: `Rp 750.000 + ⌈(N − 1.000) / 100⌉ × Rp 100.000`.

---

## Setup Lokal

### Prasyarat

- [Laravel Herd](https://herd.laravel.com/) (macOS)
- PostgreSQL 17 — disarankan via [DBngin](https://dbngin.com/)
- Node.js 18+

### Langkah

```bash
# 1. Clone & install dependencies
git clone https://github.com/fazaid/walisantri.git
cd walisantri
composer install
npm install

# 2. Konfigurasi environment
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuaikan koneksi database:

```env
APP_URL=http://walisantri.test
APP_BASE_DOMAIN=walisantri.test
APP_DOMAIN=app.walisantri.test

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=walisantri
DB_USERNAME=postgres
DB_PASSWORD=
```

```bash
# 3. Migrasi & seeding
php artisan migrate
php artisan db:seed

# 4. Build frontend assets
npm run build

# 5. Daftarkan site di Herd
# Herd → Sites → Add → pilih folder walisantri
```

Tambahkan entri di `/etc/hosts` jika subdomain tidak otomatis resolve:

```
127.0.0.1  walisantri.test
127.0.0.1  app.walisantri.test
```

> Herd menangani wildcard `*.walisantri.test` otomatis via DNS resolver — subdomain profil pesantren tidak perlu entri manual.

---

## Akun Demo

Setelah `php artisan db:seed`:

| Role | Email | Password | Pesantren |
|---|---|---|---|
| Super Admin | `superadmin@walisantri.com` | `superadmin123` | — |
| Admin Pesantren | `admin@al-fatah.com` | `admin123` | Al-Fatah |
| Admin Pesantren | `admin@ibnu-hajar.com` | `admin123` | Ibnu Hajar |
| Admin Pesantren | `admin@darul-ilmi.com` | `admin123` | Darul Ilmi |
| Ustadz | `ustadz.ibrahim@al-fatah.com` | `ustadz123` | Al-Fatah |
| Wali Santri | `wali.fulan@al-fatah.com` | `wali123` | Al-Fatah |

---

## Struktur Database

Migrasi dipisah dua direktori:

```
database/migrations/
├── central/   → pesantrens, users, tenant_domains, slug_releases, activity_logs
└── tenant/    → santri, tahfidz_*, kesantrian_*, master_pengumuman
```

Model tenant menggunakan trait `Multitenantable` — Global Scope otomatis menambahkan `WHERE pesantren_id = ?` pada setiap query; `super_admin` di-bypass.

---

## Testing

```bash
# Unit tests (SQLite in-memory)
php artisan test --testsuite=Unit

# Tenant isolation tests (wajib PostgreSQL)
php artisan test --testsuite=TenantIsolation

# Semua tests paralel
php artisan test --parallel
```

> `DataIsolationTest` membutuhkan koneksi PostgreSQL aktif — tidak bisa SQLite karena menguji isolasi data lintas tenant.

---

## Scheduled Jobs

Daftarkan cron di server:

```bash
* * * * * cd /path/to/walisantri && php artisan schedule:run >> /dev/null 2>&1
```

| Job | Jadwal | Fungsi |
|---|---|---|
| `CheckExpiredTenants` | Tiap jam | Auto-suspend tenant yang sudah expired |
| `WarnExpiringTenants` | Tiap hari 08.00 | Email peringatan 7 hari sebelum expired |
| `PurgeAuditLogs` | Tiap minggu | Hapus log audit > 90 hari |
| `DatabaseBackup` | Tiap hari 02.00 | `pg_dump` → Cloudflare R2 |
| `WarmDashboardCache` | Tiap 25 menit | Pre-generate cache dashboard wali |
| `PruneStaleCache` | Tiap hari 03.00 | Bersihkan cache kadaluwarsa |

---

## Development

```bash
# Dev server dengan HMR
npm run dev

# Lihat semua route
php artisan route:list

# Buat user super admin baru
php artisan db:seed --class=SuperAdminSeeder
```
