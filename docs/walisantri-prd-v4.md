# PRODUCT REQUIREMENTS DOCUMENT (PRD)

**Project:** Walisantri.com (v1.0) — B2B Multi-Tenant SaaS (Hybrid: single-DB sekarang, schema/DB-per-tenant ready)
**Stack:** Laravel 13.11.1 (PHP 8.3+), Filament v5.6.3, Livewire v3, TailwindCSS, PostgreSQL 17, Redis, Cloudflare R2
**Dev/Deploy:** Laravel Herd (macOS) · GitHub Actions → VPS via SSH (deploy host-langsung, tanpa kontainer)
**Interface:** Mobile-first (Wali Santri), desktop-optimized (Admin/Ustadz)
**Last Updated:** Juli 2026 — v4.12

**Changelog v4.12:** (1) **Biodata: `jenis_kelamin`** — kolom `enum('laki_laki'/'perempuan') null` ditambahkan ke tabel `santri` (form & infolist di grup "Data Santri", kolom + filter di tabel daftar, ikut di-import Excel dengan parser toleran variasi teks "L"/"Laki-laki"/"P"/"Perempuan", dan ikut di export Data Santri + template import) — nullable karena data santri lama tidak punya nilai ini, sama seperti field biodata lain (lihat §3.2). (2) **Dashboard Admin Pesantren** — perbaikan actionability & kelengkapan widget yang sebelumnya belum pernah didokumentasikan di PRD (§4.6 selama ini hanya mencakup Dashboard Super Admin): widget **Tren Amalan 7 Hari** dikembalikan (sempat dihapus di window sebelum v4.9), **Status SPP** kini menampilkan total Rupiah tertunggak + tautan ke daftar tagihan, dua widget baru **Distribusi Nilai Setoran** & **Tren Setoran** (agregat seluruh pesantren, adaptasi dari widget dashboard ustadz), serta pesan empty-state untuk pesantren baru di seluruh widget chart — lihat §4.7 (baru).

**Changelog v4.11:** Rekening bank platform (untuk pembayaran manual upgrade/perpanjang langganan) dipindah dari hardcode `config('billing.bank_transfer')`/`.env` (2 slot tetap, tanpa logo, tanpa UI) ke tabel `platform_bank_accounts` (central) — jumlah bank jadi dinamis, tiap bank bisa punya `logo` (disk `public`, dibersihkan otomatis via Observer saat diganti/dihapus, pola sama `santri.foto_profil`), `urutan` tampil, dan toggle `aktif`. Resource Filament baru **Rekening Bank** (`PlatformBankAccountResource`, super_admin-only, grup navigasi Langganan) untuk CRUD-nya (lihat §3.1, §7). Halaman invoice (`OrderInvoicePage`, section "Cara Pembayaran") kini membaca dari tabel ini (hanya yang `aktif`, terurut), menampilkan logo bila ada, dan menambah tombol **"Salin"** per nomor rekening (vanilla JS clipboard, reuse pola dari modal Magic Link) — sekaligus dilengkapi varian dark-mode yang sebelumnya belum ada di section ini (lihat §16.1, baru — dokumentasi alur pembayaran manual Order/Invoice yang ternyata belum pernah ditulis di PRD).

**Changelog v4.10:** Bug fix — admin/ustadz pesantren expired/suspended tidak bisa membuka `/billing` (infinite redirect loop). Whitelist bebas-lock di `SaaSLifecycleLock` masih memakai path string hardcode `admin/billing-page`, tidak cocok lagi sejak `BillingPage` dipindah ke Cluster Pengaturan di v4.9 (URL asli `admin/pengaturan/billing-page`); diperbaiki ke pengecekan route name, dan `UpgradePage` (sebelumnya tidak pernah di-whitelist) ditambahkan. Sekaligus koreksi §5.5 — kolom Suspended untuk Admin/Ustadz seharusnya tertulis "redirect `/billing`" (tetap bisa bayar & reaktivasi mandiri), bukan "diblokir total" seperti yang tertulis sebelumnya; wali santri tetap diblokir total. Lihat §5.5.

**Changelog v4.9:** Dua modul baru, satu cluster navigasi baru, dan perubahan fundamental model data tahfidz. (1) **Tahfidz: migrasi juz-based → halaman-based** — kolom `ayat_mulai`/`ayat_selesai` pada `tahfidz_progress` diganti `halaman_mulai`/`halaman_selesai` smallint; `nama_surah` jadi nullable; `TahfidzJuzCalculator` kini menghitung `juz_hafal = min(total_halaman_unik / 20, 30)` — bukan lagi mapping ayat-per-surah presisi (lihat §3.2). (2) **Modul Ekstrakurikuler baru** — tabel `ekskul_masters` (master ekskul per-pesantren) & `santri_ekskuls` (partisipasi santri, level Pemula/Menengah/Mahir); Resource Filament masuk Cluster Akademik; tampil di Rapor Akademik & profil santri portal wali; tersedia semua paket, tanpa gate (lihat §3.2, §5.1, §7, §8). (3) **Modul Uang Saku Santri & Tarif SPP baru** — tabel `uang_saku_santri` (ledger setoran/pengambilan per santri) & `tarif_spp` (nominal SPP per kelas); Cluster **Keuangan** baru di grup Manajemen; portal wali dapat halaman `/wali/uang-saku` + tab baru di bottom nav; tersedia semua paket, tanpa gate (lihat §3.2, §5.1, §7, §8). (4) **Cluster Rapor baru** — cluster top-level menggabungkan 4 laporan (Akademik → Tahfidz → Mutabaah → Karakter) sebagai custom Page dengan filter & ekspor PDF seragam; Rapor Akademik dipindah keluar dari Cluster Akademik (lihat §7, §15). (5) **Restrukturisasi navigasi total** — grup top-level lama "Santri"/"Akademik"/"Keuangan" dibubarkan, semua jadi Filament Cluster (`Santri`, `Akademik`, `Tahfidz`, `Mutabaah`, `Kesantrian`, `Rapor` — semua top-level tanpa group; `Keuangan` & `Pengaturan` sebagai Cluster di dalam grup Manajemen bersama Pengguna & Pengumuman); `navigationGroups()` kini hanya mendaftarkan `Kesantrian, Langganan, Manajemen` (lihat §7). (6) **Business rule santri** — ustadz kini bisa mengedit santri di halaqahnya sendiri (sebelumnya create/edit admin-only, kini create tetap admin-only tapi edit admin + ustadz, lihat §5.4). (7) **Skema periode diseragamkan** — `nilai_akademik`, `kesantrian_karakter_rapor`, dan `tahfidz_rapor` sama-sama mendapat kolom `bulan`; unique constraint `nilai_akademik` bertambah `bulan` (lihat §3.2). (8) **`kesantrian_kesehatan`** bertambah `jenis_rekam` enum(`keluhan`/`rutin` — field keluhan jadi nullable saat `rutin`) & status pemulihan baru `Sembuh` + `tanggal_sembuh` (lihat §3.2). (9) **Selesai, pindah dari roadmap/di-skip:** Excel Importer massal santri (§22), foto profil santri `santri.foto_profil` (§3.2), Daftar Inventaris wali (§8/§22); `santri.wali_santri_id`/`pembimbing_ustadz_id` jadi nullable untuk mendukung import sebelum akun terkait dibuat (§3.2). (10) **Export & tiering diselaraskan ke kode aktual** — Export Rekam Medis kini didokumentasikan tanpa gate paket (sebelumnya tertulis "Berkembang+", kode tidak pernah menegakkannya — selaras v4.6 yang sudah memindahkan modul Kesehatan sendiri ke Rintisan+); koreksi §5.1 — Gate `access-modul-spp`/`access-modul-prestasi` yang sebelumnya disebut PRD ternyata tidak ada di `AppServiceProvider` (modul-modul ini memang tak pernah di-gate, hasil akhir tetap "tersedia semua paket"). (11) **Audit event** `magic_link.sent` dikoreksi jadi `magic_link.viewed` (dipicu saat modal Kirim Magic Link dibuka di Filament, bukan saat WhatsApp benar-benar terkirim — §10.2).

**Changelog v4.8:** Penyempurnaan modul Kesantrian & UX panel admin, tidak ada perubahan model bisnis. (1) **Amalan Mutaba'ah Dinamis** — kolom boolean hardcode (`jamaah_5_waktu`, `is_rawatib`, dll.) pada tabel `kesantrian_mutabaah` diganti satu kolom `amalan jsonb default '{}'`; konfigurasi amalan dikelola via tabel master baru `kesantrian_amal_master` (per-pesantren: kode, label, tipe `boolean`/`hitungan`, nilai_maks, satuan, icon, bobot, urutan, aktif) — setiap pesantren bisa menambah/menonaktifkan jenis amalan sendiri tanpa perubahan skema (lihat §3.2). (2) **Restrukturisasi navigasi Kesantrian** — "Kesantrian (group)" dipecah jadi dua **Filament Cluster** terpisah: **Cluster Mutabaah** (Mutaba'ah Harian + Amal Master) dan **Cluster Kesantrian** (Karakter Rapor + Kesehatan + Inventaris); keduanya `$navigationGroup = null` (top-level di sidebar, tidak dalam group) — lihat §7. (3) **Biodata: `tanggal_lahir`** — kolom `tanggal_lahir date null` ditambahkan ke tabel `santri` (form DatePicker, infolist, cast `date`) — lihat §3.2. (4) **UX panel admin** — sidebar Filament kini `sidebarFullyCollapsibleOnDesktop()`; tambah bottom navigation mobile di Filament admin panel via render hook `BODY_END` (view `filament.admin.bottom-nav`) — lihat §7. (5) **Dashboard wali: branching** — wali dengan tepat 1 anak aktif langsung tampil halaman detail penuh; wali dengan >1 anak tampil cards ringkasan per anak — lihat §8.

**Changelog v4.7:** Perubahan operasional & UX panel admin, tidak ada perubahan model bisnis. (1) **Git workflow** — branch `dev` ditambahkan sebagai branch kerja; CI (job `test`) jalan di push ke `dev` maupun `main`, tapi job `deploy` (SSH ke VPS) hanya jalan dari `main`; branch `main` diberi **branch protection** (wajib PR, wajib status check `Test` lolos & up-to-date, tanpa approval review wajib karena solo-dev) — lihat §6.4 & §18. (2) **Biodata Santri** — tambah kolom `nama_panggilan`, `nama_ayah`, `nama_ibu`, `alamat_lengkap`, `jumlah_saudara`, `ciri_fisik`, `cita_cita` pada tabel `santri` (lihat §3.2); tampil di form & halaman detail Filament. "Karakter dominan/Kelebihan/Kekurangan" sengaja tidak ditambahkan (tumpang tindih dengan modul Karakter Rapor yang sudah dinamis/periodik); "Suku" sengaja tidak ditambahkan (data sensitif, tanpa kebutuhan operasional jelas). (3) **Restrukturisasi navigasi Filament** — 3 resource Tahfidz (Setoran/Ujian/Nilai, sebelumnya flat di grup Akademik) digabung jadi satu **Filament Cluster "Tahfidz"** dengan navigasi tab (dipindah ke atas breadcrumbs via render hook, tampil konsisten desktop & mobile); halaman **Rapor Akademik** kini menampilkan section Nilai Akademik **dan** Nilai Tahfidz sekaligus (satu rekap + satu PDF gabungan, model data tetap terpisah); menu **Pengumuman** dipindah ke grup **Manajemen**; menu **Prestasi Santri** diberi label tampilan **Prestasi** (slug URL `/admin/prestasi-santris` → `/admin/prestasi`; nama tabel/model tidak berubah) — lihat §7. (4) **Bug fix** — field `tahun_ajaran` pada input Nilai Akademik & Rapor Tahfidz diubah dari teks bebas jadi dropdown standar (mencegah mismatch format yang menyebabkan nilai tidak muncul di rapor). (5) Landing page: hapus klaim "Tidak perlu kartu kredit · Setup 5 menit" (tidak relevan, sistem ini berbasis trial+konfirmasi manual, bukan kartu kredit otomatis).

**Changelog v4.6:** Revisi **model bisnis & harga** — (1) harga paket **Berkembang** diturunkan Rp 450.000 → **Rp 350.000**/bulan agar lompatan harga lebih gradual (rasio ×2,3 vs ×3 sebelumnya); (2) **paket Gratis dihapus** — diganti model **trial Rintisan 30 hari gratis** (kuota 100 santri, fitur penuh Rintisan) agar calon pelanggan merasakan nilai nyata sebelum berkomitmen; (3) **Modul Kesehatan** dipindah ke **Rintisan+** (sebelumnya Berkembang+) — rekam medis adalah kebutuhan keselamatan dasar boarding school, bukan fitur premium; (4) lifecycle baru: trial 30 hari → expired → **grace period 7 hari** (admin/ustadz redirect `/billing`, wali read-only) → **suspended**; (5) **paket Maju** izinkan X=0 — 1.000 santri = Rp 750.000/bulan (base price, tanpa add-on); (6) opsi durasi **6 bulan** ditambah ke §5.2 (bayar 5, aktif 6); (7) **§5.6 baru** — Kebijakan Retensi (jaminan harga terkunci, program referral); (8) simulasi bisnis & **target milestone klien** di §21 diperbarui; (9) landing page kini memiliki **seksi #harga** dengan toggle bulanan/tahunan dan 4 kartu paket; (10) **paket Tumbuh** ditambah — 250 santri, Rp 299.000/bulan, posisi "Paling Populer" (lihat §5.1); (11) **kebijakan minimum durasi upgrade** — sisa aktif > 6 bulan wajib minimum 6 bulan, sisa > 9 bulan wajib 12 bulan (lihat §16).

**Changelog v4.5:** Modul **Akademik Formal** — entitas baru `mata_pelajaran` (kelas + ustadz pengampu tetap, master data `admin_pesantren`) dan `nilai_akademik` (nilai tunggal per santri/mapel/periode, input `admin_pesantren` + `ustadz` pengampu, unique `(santri_id, mata_pelajaran_id, tahun_ajaran, periode)`); halaman **Rapor Akademik** agregasi nilai per santri dengan ekspor PDF (reuse `barryvdh/laravel-dompdf`). Grup navigasi Filament **Akademik** baru — menggabungkan Mata Pelajaran, Nilai Akademik, Rapor Akademik dengan 3 resource Tahfidz yang dipindah dari grup Kesantrian (selaras nama modul §3.2 & §5.1). Tersedia di semua paket (gate `access-modul-akademik` sudah ada sejak v4.x). Closes gap landing page yang sejak awal menjanjikan modul ini (lihat §22 — "akademik formal" kini bukan lagi item ditunda). **Selain itu:** halaman **Rapor** portal wali (`/wali/rapor` + ekspor PDF) — yang ternyata sudah lama dibangun penuh namun belum tertaut navigasi & masih tercatat keliru sebagai "roadmap/di-skip" di §8/§22 — kini ditautkan sebagai tab ke-5 bottom nav wali (Beranda · Santri · SPP · Pengumuman · **Rapor**) dan PRD diselaraskan ke status "selesai" (lihat §8); view duplikat mati `wali/pengumuman/index.blade.php` turut dibersihkan.

**Changelog v4.4:** Modul **SPP** (Sumbangan Pembinaan Pendidikan) — tagihan bulanan manual per santri, rekening bank pesantren disimpan di `profil` jsonb, konfirmasi transfer oleh wali (upload foto bukti → status `menunggu_konfirmasi`), verifikasi & tandai lunas oleh admin, notifikasi tunggakan di dashboard wali; tabel `tagihan_spp` + `pembayaran_spp` (tenant/). Modul **Prestasi Santri** — CRUD prestasi (judul, kategori, tingkat, posisi, tanggal, penyelenggara, sertifikat) dengan enum `TingkatPrestasi` (internal/kabupaten/provinsi/nasional/internasional); tabel `prestasi_santri` (tenant/); tampil di portal wali pada halaman detail santri. **Demo Request / Waiting List** — halaman `/demo` di landing page (form waiting list: nama pesantren, kontak, email, HP, jumlah santri, kota, catatan); tabel `demo_requests` (central/); `DemoRequestResource` di Filament hanya `super_admin` (list, view, tandai dihubungi). Grup navigasi **Keuangan** baru di panel Filament.

**Changelog v4.3:** `kelas` & `kamar` diangkat menjadi entitas master (tabel `kelas`, `kamar` per-tenant; kolom string di `santri` migrasi ke FK) · Resource Filament CRUD Kelas & Kamar hanya `admin_pesantren` · grup navigasi "Santri" berisi Santri, Kelas, Kamar · aturan bisnis baru: ustadz hanya bisa membimbing **maks 20 santri aktif** (validasi di form + query scope) · kebijakan **harga tahunan: bayar 10 bulan, aktif 12 bulan** (enum `DurasiLangganan` + `BillingCalculatorService`) · portal wali sudah selesai MVP: dashboard (sapaan + daftar santri + pengumuman), statistik tahfidz, statistik kesehatan, detail mutaba'ah harian per santri · billing upgrade flow selesai (pilih paket, invoice, konfirmasi admin).

**Changelog v4.2:** Super Admin dikonsolidasikan ke `app.walisantri.com/admin` — `dash.walisantri.com` & `DashPanelProvider` dihapus · satu panel Filament untuk semua role (`admin`, `admin_pesantren`, `ustadz`), visibilitas menu dikontrol `canAccess()`/`canView()` per role · widget Dashboard Central (SystemStatsWidget, ExpiringTenantsWidget, TenantListWidget) pindah ke admin panel dengan `canView()` hanya `super_admin` · IP-whitelist Nginx dialihkan ke path `/admin` di `app` · route `/admin/login` (Filament bawaan) dihapus — semua role wajib lewat `/login` terpusat (branded, `?tenant={slug}`) via `FilamentAuthenticate` middleware.

**Changelog v4.1:** Model deploy difinalkan ke **host-langsung** (bukan kontainer) demi efisiensi resource VPS 4GB — Coolify & Docker ditolak (overhead idle) · **§6.6 baru** observability ringan no-Coolify (`LOG_CHANNEL=daily` + Sentry, UptimeRobot, GoAccess on-demand, htop/ncdu, Laravel Pulse opsional) · Docker Compose dicatat di §22 sebagai keputusan tertunda dengan pemicu eksplisit.

**Changelog v4.0:** Login terpusat di `app.walisantri.com` (tenant di-resolve dari akun) · subdomain `{slug}.walisantri.com` jadi **website profil publik** (slug mutable) · custom domain di roadmap · hybrid tenancy · **PostgreSQL 17** (RLS native + pgvector untuk AI) · Cloudflare R2 · CI/CD GitHub Actions. *(v3.0: Filament v5, path-based routing, Dashboard Central. v2.0: row-level security, RBAC 4 role, modul Tahfidz & Kesantrian.)*

---

# Product Vision Statement

**Visi:** Menjadi standar digitalisasi pesantren Indonesia — platform pengasuhan & akademik terlengkap, terjangkau, dan dipercaya oleh setiap lembaga, dari rintisan hingga besar.

**Tagline:** Memberi setiap pesantren — berapapun ukurannya — kemampuan membuktikan kualitas pengasuhannya secara transparan, terukur, dan real-time.

| Pilar | Maksud | Implikasi Produk |
|---|---|---|
| Terlengkap | Satu platform: akademik, pengasuhan, kesehatan, inventaris, komunikasi | Tidak perlu sistem lain di samping Walisantri |
| Terjangkau | Mulai Rp 150.000/bulan | Paket Rintisan fungsional penuh, bukan fitur terpotong |
| Dipercaya | Data aman, terisolasi per lembaga, akuntabel | Isolasi tenant & audit log = fondasi, bukan fitur tambahan |

**Filter keputusan fitur** (jika >1 jawaban "tidak" → antrian rendah/ditolak): (1) Meningkatkan kredibilitas/akuntabilitas pesantren? (2) Bisa dirasakan paket Rintisan? (3) Mendekatkan ke posisi standar digitalisasi pesantren?

---

# 1. Architectural Foundation & Tenant Isolation

## 1.1 Row-Level Multi-Tenancy

Setiap tabel operasional wajib punya kolom `pesantren_id` (FK). Trait `Multitenantable` menyuntik `WHERE pesantren_id = auth()->user()->pesantren_id` otomatis pada SELECT/UPDATE/DELETE — kecuali `super_admin`. Model pakai PHP 8.3 Attributes:

```php
#[Table('santri')]
#[Fillable(['pesantren_id', 'wali_santri_id', 'uuid', 'nis', 'nama_lengkap', 'kelas_id', 'kamar_id', 'status_aktif'])]
class Santri extends Model {
    use Multitenantable, HasUuids, SoftDeletes;
    public function uniqueIds(): array { return ['uuid']; } // batasi HasUuids hanya ke kolom uuid
}
```

> **PostgreSQL RLS (lapisan kedua):** selain Global Scope di aplikasi, PostgreSQL Row-Level Security dapat menegakkan isolasi di level database — `ENABLE ROW LEVEL SECURITY` + policy `pesantren_id = current_setting('app.current_pesantren')::bigint`. Konteks tenant di-set per request via `SET app.current_pesantren` (dari sesi login di `app`, lihat §1.3–1.4). Defense-in-depth: jika scope aplikasi bocor, DB tetap memblokir. Aktifkan setelah trait stabil; Super Admin pakai role `BYPASSRLS`.

## 1.2 Hybrid Tenancy Strategy

- **DB Central** (`walisantri_central`, koneksi `central`): tabel `pesantrens`, `users`, `tenant_domains`, `activity_logs` — untuk autentikasi, lookup tenant dari akun, dan resolusi host publik.
- **DB Tenant** (koneksi `tenant`; saat ini single shared DB, roadmap schema-per-tenant): semua data operasional.
- `Multitenantable` Global Scope (+ RLS opsional) tetap aktif sebagai lapisan keamanan kedua selama single DB.
- Migrasi dipisah: `database/migrations/central/` & `database/migrations/tenant/`.
- `tenancy.mode` di `.env`: `single_database` (default MVP) atau `per_schema` (roadmap — schema-per-tenant native PostgreSQL via `SET search_path`).

## 1.3 Host Model, Login Terpusat & Resolusi Tenant

Empat jenis host dengan peran berbeda:

| Host | Sifat | Fungsi |
|---|---|---|
| `walisantri.com` | Publik | Landing + `/register` |
| `{slug}.walisantri.com` | Publik, tanpa auth (cacheable) | **Website profil pesantren** — subdomain **mutable** |
| `app.walisantri.com` | Terautentikasi | Login tunggal semua role → panel admin/ustadz/super_admin & portal wali |

**Login terpusat:** Semua role login di `app.walisantri.com` (satu host tetap). Tenant **di-resolve dari akun**, bukan dari host: lookup `users` by email → ambil `pesantren_id` → set konteks tenant (`app()->instance('current_pesantren', …)` + `SET app.current_pesantren` untuk RLS). Sejalan dengan model multi-tenancy native Filament v5 (satu panel, tenant dari user).

**Pintu masuk & branding wali:** Wali santri masuk **dari situs profil pesantren** — tombol "Portal Wali Santri" di `{slug}.walisantri.com` mengarah ke `app.walisantri.com/login?tenant={slug}`. Halaman login membaca `tenant` dari query dan **dirender penuh ber-brand pesantren** (logo, nama, warna) sehingga terasa seperti gerbang pesantren itu, bukan platform generik — meski host auth tetap `app`. Ini memberi keterikatan brand tanpa menduplikasi mekanisme auth atau mengikat sesi ke subdomain yang bisa berubah. **Magic Link WhatsApp (§4.3) tetap jalur utama wali** (klik langsung masuk read-only); form login adalah jalur sekunder bagi wali yang menyetel password. Tombol login admin/ustadz juga memakai `?tenant={slug}` agar branding konsisten.

> **Email unik global (keputusan sadar):** karena tenant di-resolve dari email, satu email tidak bisa dipakai di dua pesantren. Untuk MVP ini diterima — kasus wali dengan anak di pesantren berbeda memakai email sama tidak didukung. "Multi-Anak Logic" (§4.1) tetap jalan selama anak-anak di pesantren yang sama.

**Dua mode TenantResolver:**
- *Host publik* (`{slug}.walisantri.com` / custom domain): `PublicTenantResolver` cocokkan `$request->getHost()` ke tabel `tenant_domains` → `pesantren_id`. Read-only, hanya untuk render situs profil — **tidak pernah** mengakses data operasional santri.
- *App* (`app.walisantri.com`): konteks tenant dari sesi login. Host tidak dipakai untuk resolusi.

## 1.4 Website Profil Pesantren

Tiap pesantren **otomatis** mendapat situs profil publik di `{slug}.walisantri.com` segera setelah registrasi. MVP: template minimal (logo, deskripsi, alamat, kontak, galeri, feed pengumuman publik), dikelola dari panel admin. CMS/page-builder penuh = post-v1.0. Pemisahan ketat: situs publik tidak boleh membaca data santri.

**Slug rules:** huruf kecil/angka/tanda hubung, 3–30 char, tidak diawali/diakhiri hubung. Validasi real-time via `GET /check-slug/{slug}`. **Mutable** — bisa diubah kapanpun dari panel admin (aman karena tidak ada auth/magic-link yang bergantung pada subdomain; identitas kanonik = `pesantrens.id`). Tiap perubahan kena validasi reserved/format + dicatat audit (`pesantren.slug_changed`). Slug lama masuk **cooldown 90 hari** sebelum bisa diklaim tenant lain (cegah pembajakan brand). Reserved (Rule `SlugNotReserved`): `www app api admin central dash mail billing status docs blog support panel dashboard static cdn`.

**Custom domain (roadmap, add-on Maju):** pesantren pakai domain sendiri (mis. `www.pesantrenfulan.sch.id`). Butuh verifikasi kepemilikan DNS (CNAME/TXT) + SSL otomatis per domain (di luar wildcard `*.walisantri.com`). **Default: Cloudflare for SaaS / Custom Hostnames** (gratis ≤100 hostname, lalu berbayar per hostname; cert otomatis, ops paling ringan). **Fallback: Caddy on-demand TLS** (gratis penuh, untuk volume besar; wajib endpoint "ask" agar cert hanya terbit untuk hostname terverifikasi di `tenant_domains`). Subdomain bawaan tetap pakai wildcard cert yang sudah ada.

## 1.5 Infrastruktur Wildcard

Subdomain profil baru aktif otomatis tanpa sentuh DNS/config:
- Wildcard SSL `*.walisantri.com` via Certbot + Cloudflare DNS-01.
- Satu A record Cloudflare `* → IP VPS`; `app` sebagai host tetap.
- Satu server block `server_name *.walisantri.com` (Nginx; atau Caddy bila custom domain diaktifkan).

## 1.6 Routing System

| Host | Path | Pengguna |
|---|---|---|
| `walisantri.com` | `/` · `/register` · `/check-slug/{slug}` | Landing · onboarding · API cek slug (JSON) |
| `{slug}.walisantri.com` (+ custom domain) | `/` · `/pengumuman` · … | Website profil publik (read-only, tanpa auth) |
| `app.walisantri.com` | `/login` · `/admin` | Login tunggal · panel Filament (Super Admin, Admin Pesantren, Ustadz) — menu per role via `canAccess()` |
| `app.walisantri.com` | `/wali/dashboard` · `/report/{uuid}` · `/billing` | Portal wali · Magic Link read-only · billing |

## 1.7 Pola Penambahan Modul

Kontrak resmi untuk menambah modul baru — ubah pola tersirat jadi checklist eksplisit agar konsistensi terjaga lintas sesi/waktu:

1. **Tabel tenant** dengan kolom `pesantren_id` (FK) wajib + kolom domain modul.
2. **Trait `Multitenantable`** pada model (Global Scope + auto-assign `pesantren_id` saat `creating`).
3. **Composite index** `(pesantren_id, [entity_id], [tanggal])` sesuai pola query; unique constraint per-tenant bila relevan.
4. **Migrasi** di `database/migrations/tenant/` (atau `central/` bila entitas lintas-tenant). Index bernama eksplisit (batas 63 char).
5. **Gate** `access-modul-{x}` di `AppServiceProvider` + satu baris di matriks tiering (§5.1) bila modul berbayar/terkunci paket.
6. **Resource Filament** di grup navigasi yang sesuai (§7), dengan `canView()`/policy mengikuti Gate.
7. **RLS policy** per tabel (bila RLS aktif) — pola sama: `pesantren_id = current_setting('app.current_pesantren')`.
8. **Test isolasi tenant** di `tests/TenantIsolation/` + unit test business logic; wajib lulus sebelum deploy.
9. **Event audit** `{modul}.{aksi}` di `activity_logs` bila modul mengubah data sensitif.
10. **Enum yang bisa tumbuh** (kategori yang mungkin bertambah/berbeda antar-pesantren) → buat tabel referensi `master_{x}` per-tenant, **bukan** CHECK constraint hardcoded. Enum tetap (mis. `A/B/C/D`) boleh hardcoded.

> *Pola ini mulus untuk modul **per-santri** (mengikuti bentuk `santri` + modul tahfidz/kesantrian). Modul yang **bukan per-santri** (keuangan, SDM, akademik formal) menyimpang dari pola dan memicu keputusan di §22 "Batas yang Diketahui".*

---

# 2. Actors, RBAC & Login Flow

**Satu pintu login:** `app.walisantri.com/login` — semua role. Tenant di-resolve dari akun (email unik global, §1.3).

Setelah autentikasi, middleware baca `role` → redirect:

| Role | Redirect | Akses |
|---|---|---|
| `super_admin` | `app.../admin` | Kelola semua tenant, billing, kuota (lintas tenant via `withoutGlobalScope` / role `BYPASSRLS`) |
| `admin_pesantren` | `app.../admin` | Kontrol penuh data lembaga, user, impor, pemetaan kelas/kamar, profil publik, billing |
| `ustadz` | `app.../admin` | Input presensi, mutaba'ah, tahfidz, rekam medis santri binaan |
| `wali_santri` | `app.../wali/dashboard` | Portal read-only perkembangan santri |

---

# 3. Core Database Schema

PostgreSQL 17, FK constraints ketat, composite index `(pesantren_id, [entity_id], [tanggal])`. Tipe enum diimplementasikan sebagai `CHECK` constraint via Laravel migration (atau native `CREATE TYPE` bila perlu).

## 3.0 ERD

ERD dipecah dua sesuai batas hybrid-tenancy. Atribut diringkas ke kolom kunci (PK/FK/UK + pembeda); daftar kolom/index/constraint lengkap ada di §3.1–3.2. FK `santri` (`pesantren_id`, `wali_santri_id`, `pembimbing_ustadz_id`) menunjuk tabel di DB Central — FK fisik di MVP single-DB, jadi referensi logis (enforce aplikasi) saat pindah ke schema-per-tenant.

**DB Central:**

```mermaid
erDiagram
  pesantrens ||--o{ users : punya
  pesantrens ||--o{ tenant_domains : punya
  pesantrens ||--o{ activity_logs : tercatat
  users ||--o{ activity_logs : melakukan
  pesantrens {
    bigint id PK
    string nama_pesantren
    string slug UK "mutable + cooldown 90h"
    enum paket_langganan "rintisan..maju"
    enum status_berlangganan "trial..expired"
    timestamp expired_at
    int santri_count_cache
    jsonb profil "konten situs publik"
  }
  users {
    bigint id PK
    bigint pesantren_id FK "null = super_admin"
    string email UK "unik global"
    string phone_number "WhatsApp"
    enum role "4 role"
  }
  tenant_domains {
    bigint id PK
    bigint pesantren_id FK
    string hostname UK "subdomain / custom"
    enum type
    boolean is_primary
    timestamp verified_at
    enum ssl_status
  }
  activity_logs {
    bigint id PK
    bigint pesantren_id FK "null = super admin"
    bigint user_id FK
    string event
    jsonb old_values
    jsonb new_values
  }
  slug_releases {
    string slug PK "cooldown tracking"
    timestamp released_at
  }
```

**DB Tenant:**

```mermaid
erDiagram
  kelas ||--o{ santri : masuk
  kamar ||--o{ santri : tinggal
  santri ||--o{ tahfidz_progress : setoran
  santri ||--o{ tahfidz_ujian : ujian
  santri ||--o{ tahfidz_rapor : rapor
  kelas ||--o{ mata_pelajaran : memuat
  mata_pelajaran ||--o{ nilai_akademik : dinilai
  santri ||--o{ nilai_akademik : memperoleh
  santri ||--o{ santri_ekskuls : ikut
  ekskul_masters ||--o{ santri_ekskuls : diikuti
  santri ||--o{ kesantrian_mutabaah : amalan
  santri ||--o{ kesantrian_karakter_rapor : karakter
  santri ||--o{ kesantrian_kesehatan : medis
  santri ||--o{ kesantrian_inventaris : barang
  santri ||--o{ tagihan_spp : tagihan
  santri ||--o{ uang_saku_santri : ledger
  kelas ||--o{ tarif_spp : tarif
  santri ||--o{ prestasi_santri : prestasi
  tagihan_spp ||--o{ pembayaran_spp : bayar
  kelas {
    bigint id PK
    bigint pesantren_id FK
    string nama_kelas UK "unik per pesantren"
  }
  kamar {
    bigint id PK
    bigint pesantren_id FK
    string nama_kamar UK "unik per pesantren"
  }
  santri {
    bigint id PK
    bigint pesantren_id FK "ke central"
    bigint wali_santri_id FK "ke users, null"
    bigint pembimbing_ustadz_id FK "ke users, null"
    bigint kelas_id FK "ke kelas"
    bigint kamar_id FK "ke kamar"
    uuid uuid UK "token Magic Link"
    string nis "unik per pesantren"
    string nama_lengkap
    enum jenis_kelamin "laki_laki/perempuan, null"
    string foto_profil "path file, null"
    boolean status_aktif
    timestamp deleted_at "SoftDeletes"
  }
  tahfidz_progress {
    bigint santri_id FK
    bigint ustadz_id FK
    date tanggal
    enum tipe_setoran "Sabaq/Sabqi/Manzil"
    smallint halaman_mulai "halaman mushaf"
    smallint halaman_selesai "halaman mushaf"
    enum nilai_kelancaran "Mumtaz..Maqbul"
  }
  tahfidz_ujian {
    bigint santri_id FK
    bigint penguji_id FK
    date tanggal_ujian
    enum target_juz
    enum status_kelulusan "Lulus/Mengulang"
  }
  tahfidz_rapor {
    bigint santri_id FK
    string tahun_ajaran
    enum periode "Bulanan/Semester"
    string bulan "null, diisi saat periode Bulanan"
    enum nilai_tilawah "A/B/C/D"
  }
  mata_pelajaran {
    bigint id PK
    bigint pesantren_id FK
    bigint kelas_id FK "ke kelas"
    bigint ustadz_id FK "ke users — pengampu tetap"
    string nama_mapel
  }
  nilai_akademik {
    bigint id PK
    bigint pesantren_id FK
    bigint santri_id FK
    bigint mata_pelajaran_id FK
    string tahun_ajaran
    enum periode "Bulanan/Semester_Ganjil/Semester_Genap"
    string bulan "null, diisi saat periode Bulanan"
    smallint nilai "0-100"
  }
  ekskul_masters {
    bigint id PK
    bigint pesantren_id FK
    string nama
    boolean aktif
  }
  santri_ekskuls {
    bigint id PK
    bigint santri_id FK
    bigint ekskul_id FK "ke ekskul_masters"
    enum level "pemula/menengah/mahir"
    date tanggal_mulai
    boolean aktif
  }
  kesantrian_mutabaah {
    bigint santri_id FK
    date tanggal
    smallint jamaah_5_waktu
    boolean is_rawatib
    enum status_udzur "Tidak/Sakit/Haid.."
  }
  kesantrian_karakter_rapor {
    bigint santri_id FK
    date tanggal_input
    string tahun_ajaran "null"
    string bulan "null"
    enum adab "7 kolom A/B/C/D"
    enum kepribadian "9 kolom A/B/C/D"
    text log_kasus_khusus
  }
  kesantrian_kesehatan {
    bigint santri_id FK
    date tanggal_periksa
    enum jenis_rekam "keluhan/rutin"
    enum kategori_keluhan "null saat rutin"
    enum status_pemulihan "auto-udzur, + Sembuh"
    date tanggal_sembuh "null"
  }
  kesantrian_inventaris {
    bigint santri_id FK
    string kode_unik_fisik UK
    smallint kuota_regulasi_maksimal
    enum kondisi_barang "Baik/Rusak/Hilang"
  }
  tagihan_spp {
    bigint id PK
    bigint pesantren_id FK
    bigint santri_id FK
    tinyint bulan "1–12"
    smallint tahun
    int nominal "rupiah"
    date jatuh_tempo
    enum status "belum_bayar/menunggu_konfirmasi/lunas"
    string bukti_transfer "path file"
    timestamp dikonfirmasi_wali_at
  }
  pembayaran_spp {
    bigint id PK
    bigint pesantren_id FK
    bigint tagihan_spp_id FK
    int jumlah
    date tanggal_bayar
    string metode_bayar "tunai/transfer_bank/lainnya"
    bigint dicatat_oleh "FK logis ke users"
  }
  tarif_spp {
    bigint id PK
    bigint pesantren_id FK
    bigint kelas_id FK "ke kelas"
    int nominal "rupiah"
  }
  uang_saku_santri {
    bigint id PK
    bigint pesantren_id FK
    bigint santri_id FK
    enum jenis "setoran/pengambilan"
    int nominal "rupiah"
    date tanggal
    bigint dicatat_oleh "FK logis ke users"
  }
  prestasi_santri {
    bigint id PK
    bigint pesantren_id FK
    bigint santri_id FK
    string judul
    string kategori
    enum tingkat "internal..internasional"
    string posisi
    date tanggal
    string penyelenggara
    string dokumen "path sertifikat"
  }
  master_pengumuman {
    bigint id PK
    bigint pesantren_id FK "scoped, bukan per-santri"
    string judul_maklumat
    text isi_maklumat
    enum target_audience "admin/wali/semua — filter visibilitas (panel & situs publik)"
  }
```

## 3.1 DB Central

**`pesantrens`** — `id` PK · `nama_pesantren` · `slug` (unique, **mutable** + cooldown 90 hari, sumber subdomain default) · `paket_langganan` enum(`rintisan`/`tumbuh`/`berkembang`/`maju`) · `max_santri_kuota` int · `status_berlangganan` enum(`trial`/`active`/`suspended`/`expired`) · `expired_at` ts null · `santri_count_cache` int default 0 · `onboarding_completed_steps` jsonb null · `profil` jsonb null (konten situs publik: deskripsi, alamat, kontak, galeri) · timestamps. *Index: `(status_berlangganan, expired_at)`.*

**`users`** — `id` PK · `pesantren_id` FK null (null = Super Admin) · `name` · `email` unique (global) · `phone_number` null (WhatsApp) · `password` · `role` enum(`super_admin`/`admin_pesantren`/`ustadz`/`wali_santri`) · `remember_token` · timestamps. *Index: `(pesantren_id, role)`.*

**`tenant_domains`** — `id` PK · `pesantren_id` FK · `hostname` unique (mis. `al-hidayah.walisantri.com` atau `www.pesantrenfulan.sch.id`) · `type` enum(`subdomain`/`custom`) · `is_primary` bool · `verified_at` ts null · `ssl_status` enum(`pending`/`active`/`failed`) · timestamps. *Sumber kebenaran resolusi host publik (`PublicTenantResolver`). MVP: baris `type=subdomain` diisi otomatis saat registrasi/ubah slug; baris `custom` tidur sampai fitur custom domain aktif.* · `slug_releases` (cooldown): `slug` · `released_at` — cek di validasi sebelum slug bisa diklaim ulang.

**`demo_requests`** — `id` PK · `nama_pesantren` · `nama_kontak` · `email` · `no_hp` · `jumlah_santri` null · `kota` null · `catatan` text null · `contacted_at` ts null (diisi admin saat pesantren dihubungi) · timestamps. *Tabel central, diisi dari halaman `/demo` di landing page; dikelola `DemoRequestResource` hanya `super_admin`.*

**`platform_bank_accounts`** *(v4.11)* — `id` PK · `bank` string · `nomor_rekening` string · `atas_nama` string · `logo` string null (path disk `public`, directory `bank-logos`) · `urutan` smallint default 0 · `aktif` bool default true · timestamps. Rekening bank **platform** Walisantri untuk pembayaran manual upgrade/perpanjang langganan (lihat §16.1) — berbeda dari `pesantrens.profil['rekening']` yang merupakan rekening **pesantren** untuk SPP wali santri. Dikelola `PlatformBankAccountResource` hanya `super_admin`; hanya baris `aktif=true` yang tampil di halaman invoice, terurut `urutan`. Menggantikan `config('billing.bank_transfer')` (dihapus di v4.11 — sebelumnya hardcode 2 slot dari `.env`, tanpa logo, tanpa UI pengelolaan).

## 3.2 DB Tenant

**`kelas`** — `id` PK · `pesantren_id` FK cascadeOnDelete · `nama_kelas` string · timestamps. *Unique: `(pesantren_id, nama_kelas)`.* Hanya `admin_pesantren` yang bisa CRUD.

**`kamar`** — `id` PK · `pesantren_id` FK cascadeOnDelete · `nama_kamar` string · timestamps. *Unique: `(pesantren_id, nama_kamar)`.* Hanya `admin_pesantren` yang bisa CRUD.

**`santri`** — `id` PK · `pesantren_id` FK cascadeOnDelete · `wali_santri_id` FK→users restrictOnDelete, **nullable (v4.9)** · `pembimbing_ustadz_id` FK→users restrictOnDelete, **nullable (v4.9)** · `kelas_id` FK→kelas nullOnDelete · `kamar_id` FK→kamar nullOnDelete · `uuid` unique (token Magic Link) · `nis` (unique per pesantren) · `nama_lengkap` · `nama_panggilan` null · `tanggal_lahir` date null · `jenis_kelamin` enum(`laki_laki`/`perempuan`) null (v4.12) · `nama_ayah` null · `nama_ibu` null · `alamat_lengkap` text null · `jumlah_saudara` smallint null · `ciri_fisik` text null (ciri fisik yang mudah dikenali) · `cita_cita` null · `foto_profil` string null (path file, v4.9) · `status_aktif` bool default true · `deleted_at` (SoftDeletes) · timestamps. *Index: `(pesantren_id, status_aktif)`, `(pesantren_id, kamar_id)`, `(pesantren_id, kelas_id)`; Unique: `(pesantren_id, nis)`.* Kolom `kelas`/`kamar` string dihapus (migrasi ke FK di v4.3). Kolom biodata (`nama_panggilan` s.d. `cita_cita`) ditambah di v4.7 — semua nullable, diisi opsional oleh admin/ustadz. `tanggal_lahir` ditambah di v4.8. **v4.9:** `wali_santri_id`/`pembimbing_ustadz_id` dibuat nullable agar bulk import Excel bisa membuat baris santri sebelum akun wali/ustadz terkait dibuat; `foto_profil` ditambah (FileUpload validasi magic-bytes, `SantriObserver` membersihkan file lama saat diganti/dihapus). **v4.12:** `jenis_kelamin` ditambah — enum PHP `App\Enums\JenisKelamin`, nullable (data lama tidak punya nilai), diisi opsional lewat form/import Excel (parser `SantriImport` toleran variasi teks "L"/"Laki-laki"/"P"/"Perempuan", case-insensitive).

### Modul Akademik & Tahfidz

**`tahfidz_progress`** — FK `pesantren_id`/`santri_id`/`ustadz_id` · `tanggal` · `tipe_setoran` enum(`Sabaq`/`Sabqi`/`Manzil`) · `nama_surah` string(100) null (v4.9: dibuat nullable) · `halaman_mulai`/`halaman_selesai` smallint null (v4.9: menggantikan `ayat_mulai`/`ayat_selesai`, satuan halaman mushaf) · `nilai_kelancaran` enum(`Mumtaz`/`Jayyid Jiddan`/`Jayyid`/`Maqbul`) · `catatan_evaluasi` text null. *Index: `(pesantren_id, santri_id, tanggal)`.* **v4.9 — migrasi juz-based → halaman-based:** kolom `ayat_mulai`/`ayat_selesai` dihapus; `TahfidzJuzCalculator::calculate()` kini menghitung `juz_hafal = min(count(halaman unik tercakup) / 20, 30)` dari seluruh setoran santri — bukan lagi mapping ayat-per-surah presisi via `QuranJuz` (kelas ini dihapus).

**`tahfidz_ujian`** — `penguji_id` FK→users · `tanggal_ujian` · `target_juz` enum(1/3/5/10/15/20/25/30) · `status_kelulusan` enum(`Lulus`/`Mengulang`) · `catatan_ujian` text null.

**`tahfidz_rapor`** — `tahun_ajaran` (`"2026/2027"`) · `periode` enum(`Bulanan`/`Semester_Ganjil`/`Semester_Genap`) · `bulan` string(10) null (v4.9, diisi saat `periode='Bulanan'`) · `nilai_hafalan` (auto) · `nilai_tilawah`/`makhraj`/`tajwid` enum A/B/C/D · `rekomendasi_pembimbing` text. *Unique: `(santri_id, tahun_ajaran, periode)`.*

**`mata_pelajaran`** — `id` PK · `pesantren_id` FK cascadeOnDelete · `kelas_id` FK→kelas cascadeOnDelete · `ustadz_id` FK→users cascadeOnDelete null (pengampu tetap — satu mapel = satu ustadz, bukan pivot many-to-many) · `nama_mapel` string(100) · timestamps. *Unique: `(pesantren_id, kelas_id, nama_mapel)`; Index: `(pesantren_id, kelas_id)`.* Master data, hanya `admin_pesantren` yang bisa CRUD (pola sama `kelas`/`kamar`).

**`nilai_akademik`** — `id` PK · `pesantren_id` FK cascadeOnDelete · `santri_id` FK→santri cascadeOnDelete · `mata_pelajaran_id` FK→mata_pelajaran cascadeOnDelete · `tahun_ajaran` string(10) (`"2026/2027"`) · `periode` enum(`Bulanan`/`Semester_Ganjil`/`Semester_Genap`) · `bulan` string(10) null (v4.9, diisi saat `periode='Bulanan'`, tampil sebagai pilihan bulan di form) · `nilai` smallint (0-100, nilai tunggal — bukan komponen berbobot tugas/UTS/UAS, mengikuti kesederhanaan `tahfidz_rapor`) · `catatan` text null · timestamps. *Unique: `(santri_id, mata_pelajaran_id, tahun_ajaran, periode, bulan)` (v4.9, sebelumnya tanpa `bulan`); Index: `(pesantren_id, santri_id, tahun_ajaran, periode)`.* Input oleh `admin_pesantren` + `ustadz` (ustadz dibatasi hanya mapel yang ia ampu, via `mata_pelajaran.ustadz_id`); validasi mencegah duplikasi periode yang sama. **Rapor Akademik** dihitung on-the-fly (agregasi rata-rata per mapel/periode) — tidak ada tabel `rapor_akademik` tersimpan, ekspor PDF via halaman Filament khusus di Cluster Rapor (v4.9, lihat §7).

### Modul Ekstrakurikuler *(v4.9)*

**`ekskul_masters`** — `id` PK · `pesantren_id` FK cascadeOnDelete · `nama` string · `deskripsi` text null · `pengajar` string null · `aktif` bool default true · timestamps. *Index: `pesantren_id`.* Master data ekskul per-pesantren (mis. Silat, Kaligrafi), hanya `admin_pesantren` yang bisa CRUD. Masuk Cluster Akademik.

**`santri_ekskuls`** — `id` PK · `pesantren_id` FK · `santri_id` FK→santri cascadeOnDelete · `ekskul_id` FK→ekskul_masters cascadeOnDelete · `level` enum(`pemula`/`menengah`/`mahir`) default `pemula` · `tanggal_mulai` date · `aktif` bool default true · timestamps. *Unique: `(santri_id, ekskul_id)`; Index: `pesantren_id`.* Partisipasi santri per ekskul, input `admin_pesantren` + `ustadz`. Tampil sebagai "Ekstrakurikuler Aktif" di Rapor Akademik (§7) dan di detail santri portal wali (§8). Tersedia semua paket, tanpa Gate.

### Modul Kesantrian & Logistik

**`kesantrian_amal_master`** *(v4.8)* — `id` PK · `pesantren_id` FK cascadeOnDelete · `kode` string(50) (slug amalan, mis. `jamaah_5_waktu`) · `label` string(100) (tampilan UI) · `tipe` enum(`boolean`/`hitungan`) — boolean = centang ya/tidak, hitungan = angka 0–`nilai_maks` · `nilai_maks` smallint null (untuk tipe hitungan; null = boolean) · `satuan` string(20) default `'hari'` (mis. `'waktu'` untuk berjamaah) · `icon` string(10) null (emoji) · `bobot` smallint default 7 (dipakai kalkulasi skor) · `urutan` smallint default 0 · `aktif` bool default true · timestamps. *Unique: `(pesantren_id, kode)`.* Master data per-pesantren; diisi default 7 amalan saat registrasi. Hanya `admin_pesantren` yang bisa CRUD via Filament Cluster Mutabaah.

**`kesantrian_mutabaah`** — `tanggal` · `amalan` jsonb default `'{}'` (key = `kode` amalan dari `kesantrian_amal_master`, value = bool atau int sesuai tipe) · `status_udzur` enum(`Tidak`/`Sakit`/`Haid`/`Izin_Pulang`/`Tugas_Pondok`). *Unique: `(santri_id, tanggal)`; Index: `(pesantren_id, santri_id, tanggal)`.* Skema kolom boolean hardcode (`jamaah_5_waktu`, `is_rawatib`, dll.) diganti satu kolom `amalan jsonb` di v4.8 (migrasi `000008`); isi amalan mengikuti master per-pesantren.

**`kesantrian_karakter_rapor`** — `tahun_ajaran` string(9) null (v4.9) · `bulan` string(10) null (v4.9, diisi saat periode bulanan) · 7 kolom Adab (`adab_ustadz`/`adab_tamu`/`adab_asrama`/`adab_kelas`/`adab_sholat`/`adab_quran`/`adab_minum`) + 9 kolom Kepribadian, semua enum A/B/C/D default B · `log_kasus_khusus` text null. *Index eksplisit `idx_karakter_ps_tgl` pada `(pesantren_id, santri_id, tanggal_input)` — nama eksplisit wajib (batas identifier PostgreSQL 63 char).* **v4.9:** `tahun_ajaran`/`bulan` ditambah sebagai identitas periode utama (selaras pola `nilai_akademik`/`tahfidz_rapor`); validasi mencegah satu santri diinput dua kali untuk periode yang sama.

**`kesantrian_kesehatan`** — `tanggal_periksa` · `jenis_rekam` enum(`keluhan`/`rutin`) default `keluhan` (v4.9) · `berat_badan`/`tinggi_badan` float null · `kategori_keluhan` enum(`Demam`/`Batuk_Pilek`/`Sakit_Perut`/`Pusing`/`Kulit_Gatal`/`Luka_Fisik`/`Lainnya`), **nullable saat `jenis_rekam='rutin'`** (v4.9) · `detail_keluhan_teks` text null · `tindakan_dan_obat` text, nullable saat `rutin` (v4.9) · `status_pemulihan` enum(`Rawat_Mandiri`/`Istirahat_Total`/`Rujukan_Luar`/`Sembuh`), nullable saat `rutin` (v4.9) · `tanggal_sembuh` date null (v4.9). *Observer: `Istirahat_Total`/`Rujukan_Luar` → auto-set `status_udzur = Sakit` di mutaba'ah harian.* **v4.9:** rekam kesehatan kini bisa dicatat sebagai `rutin` (pemeriksaan berkala tanpa keluhan) selain `keluhan` (sakit) — form menyembunyikan section Keluhan otomatis saat `rutin`; nilai `Sembuh` ditambah ke `status_pemulihan` + kolom `tanggal_sembuh` untuk menandai pemulihan penuh.

**`kesantrian_inventaris`** — `nama_barang_umum` · `kode_unik_fisik` unique (`[Inisial]-[Barang]-[Nomor]`, mis. `FZ-SRG-01`) · `kuota_regulasi_maksimal` smallint · `kondisi_barang` enum(`Baik`/`Layak_Rusak`/`Hilang`) · `tanggal_sidak_terakhir` date null.

**`master_pengumuman`** — `judul_maklumat` · `isi_maklumat` text · `target_audience` enum(`admin`/`wali`/`semua`, default `semua`) — kontrol visibilitas: filter feed dashboard wali & **feed pengumuman publik** di `{slug}.walisantri.com` (§1.4) hanya menampilkan `wali`/`semua`, menyembunyikan pengumuman ber-target `admin` dari situs publik · timestamps. *Index: `(pesantren_id, created_at)`.*

### Modul Keuangan

**`tagihan_spp`** — FK `pesantren_id`/`santri_id` · `bulan` tinyint (1–12) · `tahun` smallint · `nominal` int (rupiah) · `jatuh_tempo` date null · `keterangan` string default `'SPP Bulanan'` · `status` enum(`belum_bayar`/`menunggu_konfirmasi`/`lunas`) default `belum_bayar` · `bukti_transfer` string null (path file foto) · `dikonfirmasi_wali_at` ts null. *Unique: `(pesantren_id, santri_id, bulan, tahun)` (nama pendek: `tagihan_spp_unik_per_bulan`); Index: `(pesantren_id, bulan, tahun)`, `(pesantren_id, santri_id)`.* Akses: hanya `admin_pesantren` + `super_admin` via Filament; wali baca-saja via portal `/wali/spp`.

**`pembayaran_spp`** — FK `pesantren_id`/`tagihan_spp_id` · `jumlah` int · `tanggal_bayar` date · `metode_bayar` string default `'tunai'` (`tunai`/`transfer_bank`/`lainnya`) · `catatan` text null · `dicatat_oleh` bigint null (FK logis ke `users.id` central — tidak di-enforce FK fisik). *Index: `(pesantren_id, tagihan_spp_id)`.*

**Alur konfirmasi transfer:** Wali tap "Saya Sudah Transfer" di `/wali/spp` → upload foto bukti → status tagihan berubah ke `menunggu_konfirmasi` + `dikonfirmasi_wali_at` diisi. Admin Filament lihat badge `!` pada aksi "Tandai Lunas" bila ada bukti masuk → review foto (ImageEntry di Infolist) → konfirmasi → status jadi `lunas` + insert baris `pembayaran_spp`.

**Rekening Bank Pesantren:** disimpan di `pesantrens.profil` jsonb sebagai array `rekening` (key: `nama_bank`, `nomor_rekening`, `atas_nama`). Dikelola via Repeater di `PesantrenSettingsPage`. Tampil di `/wali/spp` agar wali tahu ke mana mentransfer.

**`tarif_spp`** *(v4.9)* — `id` PK · `pesantren_id` FK→pesantrens cascadeOnDelete · `kelas_id` FK→kelas cascadeOnDelete · `nominal` unsignedInteger (rupiah) · `keterangan` string null · timestamps. *Unique: `(pesantren_id, kelas_id)`.* Nominal SPP standar per kelas, hanya `admin_pesantren` yang bisa CRUD, masuk Cluster Keuangan.

**`uang_saku_santri`** *(v4.9)* — `id` PK · `pesantren_id` FK cascadeOnDelete · `santri_id` FK→santri cascadeOnDelete · `jenis` string via enum `JenisUangSaku` (`setoran`/`pengambilan`) · `nominal` unsignedInteger (rupiah) · `tanggal` date · `keterangan` text null · `dicatat_oleh` bigint null (FK logis ke `users.id`) · timestamps. *Index: `(pesantren_id, santri_id)`, `(pesantren_id, tanggal)`.* Ledger transaksi uang saku santri (titipan orang tua, diambil bertahap), hanya `admin_pesantren` yang bisa CRUD via panel; wali baca-saja via `/wali/uang-saku` (saldo = akumulasi setoran − pengambilan). Tersedia semua paket, tanpa Gate.

### Modul Prestasi

**`prestasi_santri`** — FK `pesantren_id`/`santri_id` · `judul` string · `kategori` string (bebas teks, mis. "Hafalan Qur'an", "Olahraga", "Sains") · `tingkat` enum(`internal`/`kabupaten`/`provinsi`/`nasional`/`internasional`) · `posisi` string null (mis. "Juara 1") · `tanggal` date · `penyelenggara` string null · `keterangan` text null · `dokumen` string null (path sertifikat/piagam). *Index: `(pesantren_id, santri_id)`, `(pesantren_id, tingkat)`.*

---

# 4. System Flows & Automation

## 4.1 Onboarding & Registrasi

Via `walisantri.com/register`. Sistem otomatis: (1) validasi slug (format, unik, reserved, cooldown) real-time; (2) buat baris `pesantrens` di central; (3) buat baris `tenant_domains` (`type=subdomain`, `{slug}.walisantri.com`); (4) **aktifkan situs profil publik** di subdomain itu (template minimal); (5) buat user pertama role `admin_pesantren`; (6) aktifkan **trial Rintisan 30 hari** (`paket_langganan='rintisan'`, `status_berlangganan='trial'`, `max_santri_kuota=100`, `expired_at=+30 hari`) — fitur penuh Rintisan tersedia selama trial; (7) redirect ke `app.walisantri.com/admin`.

> **Zero-Self Registration:** Santri/Ustadz/Wali tidak bisa daftar mandiri. **Multi-Anak Logic:** jika nomor WhatsApp wali sudah terdaftar, santri baru dikaitkan ke `wali_santri_id` yang ada.

## 4.2 Grid Input Massal

UI Grid/Table Livewire untuk input mutaba'ah massal per kamar dalam satu layar — filter visual per `kamar`, toggle amalan kolektif untuk efisiensi mobile.

## 4.3 Magic Link (Passwordless, On-Demand)

Wali akses portal tanpa password. Dipicu **manual** oleh Admin/Ustadz (bukan scheduler):
1. Buka data santri di Filament → aksi **Kirim Magic Link ke Wali**.
2. Dispatch job `KirimNotifikasiWhatsapp` ke queue `whatsapp-notif`, payload URL `app.walisantri.com/report/{santri:uuid}` (host tetap — kebal perubahan subdomain).
3. Middleware `VerifyMagicToken` tangkap UUID → cocokkan ke `santri` → auto-login read-only.
4. Semua request non-GET dari sesi Magic Link → abort 403.
5. Tanpa expiry; berlaku selama UUID tidak di-regenerate manual oleh Admin.

> Konteks umum: rapor baru, santri masuk `Rujukan_Luar`, pengumuman penting.

## 4.4 Queue Routing Terpusat (Laravel 13)

```php
// AppServiceProvider::boot() — cek class_exists() sebelum daftar
Queue::route(KirimNotifikasiWhatsapp::class, connection: 'redis', queue: 'whatsapp-notif');
Queue::route(ProsesImporSantri::class, connection: 'redis', queue: 'bulk-import');
Queue::route(KalkulasiRaporTahfidz::class, connection: 'redis', queue: 'rapor-calc');
```

## 4.5 Cache Strategy

Cache 30 menit per santri untuk dashboard wali: `Cache::touch("dashboard_wali:{$santriUuid}", now()->addMinutes(30))`. Super Admin dashboard pakai `santri_count_cache` di `pesantrens` (di-update Observer), bukan `COUNT()` realtime.

## 4.6 Dashboard Central Super Admin (`app.walisantri.com/admin`)

Panel Filament yang sama dengan Admin/Ustadz; menu ditampilkan via `canAccess()`/`canView()` per role. Widget super admin: **SuperAdminStatsOverview** (pesantren aktif/trial, total santri, akan expired, bermasalah) · **SystemStatsWidget** (total user/ustadz/wali) · **ExpiringTenantsWidget** (tabel pesantren expired ≤7 hari) · **TenantListWidget** (tabel semua pesantren + aksi Suspend/Aktifkan). Semua `canView()` hanya `super_admin`, query `withoutGlobalScope('pesantren')`, angka agregat dari `santri_count_cache`.

## 4.7 Dashboard Admin Pesantren *(v4.12 — baru terdokumentasi)*

Widget yang tampil untuk `admin_pesantren` (semua `canView()` cek role ini, semua query di-scope `pesantren_id` milik user login):

- **AdminStatsOverview** — 6 stat card: Santri Aktif (vs kuota paket), Ustadz Terdaftar, Wali Santri, Santri Sakit Hari Ini, Amalan Minggu Ini (rata-rata), Langganan (status + sisa hari, tautan ke `BillingPage`).
- **AdminTrendAmalanChart** — line chart rata-rata persentase amalan seluruh santri, 7 hari terakhir. Pesan empty-state kalau belum ada data mutaba'ah.
- **AdminNilaiSetoranChart** & **AdminTrendSetoranChart** *(v4.12, baru)* — half-width berdampingan: distribusi nilai kelancaran setoran (Mumtaz/Jayyid Jiddan/Jayyid/Maqbul) dan tren jumlah setoran per hari, keduanya agregat seluruh santri pesantren 7 hari terakhir. Adaptasi dari widget dashboard ustadz (`UstadzNilaiSetoranChart`/`UstadzTrendSetoranChart`) yang aslinya di-scope per santri binaan — versi admin menghapus filter `pembimbing_ustadz_id` supaya mencakup semua ustadz. Sebelum v4.12 dashboard admin tidak punya widget Tahfidz sama sekali.
- **AdminSppStatusChart** & **AdminKesehatanTrendChart** — half-width berdampingan: doughnut status SPP bulan berjalan (kini menampilkan total Rupiah tertunggak + tautan ke daftar tagihan `belum_bayar` di `TagihanSppResource`, v4.12) dan line chart tren insiden kesehatan (filter periode 7/14/30 hari). Keduanya dilengkapi pesan empty-state untuk pesantren baru (v4.12).
- **PengumumanCentralWidget** — full-width, tampil hanya kalau ada pengumuman pusat aktif (hidden-when-empty).

> Dashboard `ustadz` punya widget analog (per santri binaan, bukan seluruh pesantren) — belum didokumentasikan penuh di PRD, di luar cakupan v4.12.

---

# 5. Business Logic & Feature Lock

## 5.1 Tiering & Gate

Matriks fitur — paket di kolom, fitur/kuota/modul di baris (✓ = termasuk, — = tidak, teks = detail):

| Fitur | Rintisan | Tumbuh | Berkembang | Maju |
|---|---|---|---|---|
| **Harga / bulan** | Rp 150.000 | Rp 299.000 | Rp 350.000 | Rp 750.000 |
| **Trial gratis** | ✓ 30 hari | — | — | — |
| **Posisi** | Starter | **Paling Populer** | Menengah | Enterprise |
| **Kuota santri** | ≤ 100 | ≤ 250 | ≤ 500 | ≤ 1.000 (+ add-on) |
| Website profil publik | ✓ | ✓ | ✓ | ✓ |
| Portal Wali + Magic Link | ✓ | ✓ | ✓ | ✓ |
| Pengumuman | ✓ | ✓ | ✓ | ✓ |
| Audit log | ✓ | ✓ | ✓ | ✓ |
| Modul Akademik & Tahfidz | ✓ | ✓ | ✓ | ✓ |
| Mutaba'ah harian | ✓ | ✓ | ✓ | ✓ |
| Karakter Rapor | ✓ | ✓ | ✓ | ✓ |
| Export Excel/PDF (termasuk rekam medis) | ✓ | ✓ | ✓ | ✓ |
| Modul SPP (tagihan bulanan) | ✓ | ✓ | ✓ | ✓ |
| Modul Prestasi Santri | ✓ | ✓ | ✓ | ✓ |
| Modul Kesehatan | ✓ | ✓ | ✓ | ✓ |
| Modul Ekstrakurikuler *(v4.9)* | ✓ | ✓ | ✓ | ✓ |
| Modul Uang Saku & Tarif SPP *(v4.9)* | ✓ | ✓ | ✓ | ✓ |
| Modul Inventaris | — | — | — | ✓ |
| Fitur AI *(post v1.0)* | — | — | — | ✓ |
| Custom domain *(roadmap, add-on)* | — | — | — | ✓ (add-on) |
| Kuota custom (> 1.000, add-on per +100) | — | — | — | ✓ |

**Gate (di `AppServiceProvider`):** `access-modul-akademik` (semua) · `access-modul-kesehatan` (Rintisan+) · `access-modul-inventaris` (Maju) · `access-modul-ai` (Maju) · `access-billing` (Admin & Super Admin). **Catatan (v4.9, koreksi):** modul Prestasi, SPP, Ekstrakurikuler, dan Uang Saku & Tarif SPP **tidak memiliki Gate sama sekali** — otomatis tersedia di semua paket by design (bukan oversight), sejalan filosofi Product Vision "paket Rintisan fungsional penuh, bukan fitur terpotong". Versi PRD sebelumnya keliru menyebut gate `access-modul-prestasi`/`access-modul-spp` yang sebenarnya tidak ada di kode; hasil akhir tetap sama ("tersedia semua paket"), hanya penamaan mekanismenya yang dikoreksi. Export Rekam Medis sebelumnya tertulis dibatasi "Berkembang+" — dikoreksi karena `ExportController::rekamMedis()` hanya memvalidasi role (`admin_pesantren`/`ustadz`), tanpa Gate paket.

> *Tidak ada paket Gratis — konversi didorong via trial Rintisan 30 hari gratis (fitur penuh, 100 santri). Paket **Tumbuh** (250 santri, Rp 299.000) adalah paket paling populer — sweet spot antara harga terjangkau dan kapasitas nyata untuk mayoritas pesantren. Setelah trial berakhir: grace period 7 hari → suspended.*

## 5.2 Kebijakan Harga Tahunan

Diskon berlangganan tahunan via enum `DurasiLangganan`:

| Durasi | Bulan Bayar | Bulan Aktif | Keterangan |
|---|---|---|---|
| Bulanan | 1 | 1 | Tanpa diskon |
| 6 Bulan | 5 | 6 | Bayar 5, gratis 1 bulan (~16,7%) |
| 12 Bulan | 10 | 12 | Bayar 10, gratis 2 bulan (~16,7%) |

Kalkulasi di `BillingCalculatorService` pakai `bulanBayar()` (bukan `value`) untuk total harga, dan `totalBulan()` untuk menambah `expired_at`. UI billing menampilkan "Durasi bayar: X bulan · Gratis: +Y bulan · Total aktif: Z bulan."

## 5.3 Formula Kuota Custom Maju (`BillingCalculatorService`)

Base paket Maju: 1.000 santri = Rp 750.000/bulan (X=0). Add-on per blok 100 santri di atas 1.000: `X = CEIL((N - 1000) / 100)` · `Total = Rp 750.000 + (X × Rp 100.000)` · `Kuota = 1000 + (X × 100)`.
Contoh: 1.200 santri → X=2 → kuota 1.200 → Rp 950.000/bulan. Contoh X=0: 1.000 santri → Rp 750.000/bulan, kuota 1.000.

## 5.4 Aturan Pembimbing Ustadz

Satu ustadz hanya dapat membimbing **maks 20 santri aktif** (`status_aktif = true`). Validasi dilakukan di dua lapisan:
- **Form Filament:** dropdown ustadz pembimbing menampilkan kuota `(X/20)` per ustadz; validasi mencegah simpan jika ustadz sudah mencapai 20.
- **Query scope Santri:** ustadz hanya melihat santri yang dia bimbing (`getEloquentQuery` filter `pembimbing_ustadz_id`); **create** santri hanya `admin_pesantren`, **edit** santri (v4.9) kini `admin_pesantren` + `ustadz` (ustadz dibatasi ke santri di halaqahnya sendiri via `pembimbing_ustadz_id` miliknya).

> *Aturan ini diterapkan di lapisan aplikasi (bukan DB constraint) agar fleksibel bila limit perlu disesuaikan per pesantren di masa depan.*

## 5.5 Middleware

- **`CheckTenantQuota`:** saat simpan `Santri`, `COUNT` santri aktif; jika `≥ max_santri_kuota` → batalkan, HTTP 422.
- **`SaaSLifecycleLock`:**

| Status | Admin/Ustadz | Wali Santri |
|---|---|---|
| Trial (30 hari) | Akses penuh + banner sisa hari | Normal |
| Active | Akses penuh | Akses penuh |
| Expired (grace 7 hari) | Redirect `/billing`, input diblokir | Read-only + banner "langganan berakhir" |
| Suspended (setelah 7 hari grace) | Redirect `/billing` (v4.10, koreksi — tetap bisa bayar & reaktivasi mandiri, bukan diblokir total) | Diblokir total |
| Subdomain not found | 404 bertema Walisantri | 404 bertema Walisantri |

> *Grace period 7 hari setelah `expired_at` diimplementasikan di `CheckExpiredTenants` job (harian 00.01): step 1 — `trial`/`active` → `expired` saat `expired_at < now()`; step 2 — `expired` → `suspended` saat `expired_at < now() - 7 hari`.*

> **v4.10 — fix redirect-loop billing:** whitelist route bebas-lock di `SaaSLifecycleLock` sempat memakai path string hardcode `admin/billing-page`, yang berhenti cocok setelah `BillingPage` dipindah ke dalam Cluster `PengaturanPesantren` (v4.9, URL asli jadi `admin/pengaturan/billing-page`) — akibatnya admin/ustadz expired/suspended kena infinite redirect loop saat mencoba buka billing (bukan bisa diakses seperti seharusnya). Diperbaiki dengan mengecek route name (`filament.admin.pengaturan.pages.billing-page`, `filament.admin.pages.order-invoice-page`, `filament.admin.pages.upgrade-page`) alih-alih path string, sekaligus menambah `UpgradePage` yang sebelumnya belum pernah di-whitelist sama sekali. Baris `Suspended` di tabel atas juga dikoreksi — sebelumnya salah tertulis "diblokir total" untuk Admin/Ustadz, padahal kode (yang dipertahankan sengaja) tetap mengizinkan mereka ke `/billing` agar bisa bayar & reaktivasi tanpa menunggu Super Admin.

## 5.6 Kebijakan Retensi

**Jaminan harga terkunci:** Tenant yang aktif berlangganan berbayar tidak dikenai kenaikan harga selama masa aktif — harga terkunci pada saat pertama kali berlangganan. Kenaikan harga hanya berlaku untuk pelanggan baru atau setelah jeda berlangganan (status `expired`/`suspended`). Dikomunikasikan eksplisit di halaman `/billing` sebagai nilai jual.

**Program Referral:** Admin pesantren yang berhasil mereferensikan 1 pesantren baru hingga berlangganan berbayar mendapatkan **1 bulan gratis** (dikreditkan ke tagihan bulan berikutnya). Dikelola manual oleh Super Admin via panel Filament — tidak ada otomasi tracking kode referral di MVP.

> *Kedua kebijakan ini tidak butuh perubahan skema DB di MVP — cukup dicatat di dashboard billing dan dieksekusi manual oleh Super Admin. Otomasi kode referral bisa dibangun saat volume klien sudah signifikan.*

---

# 6. Infrastruktur Production

## 6.1 Stack Server

VPS Debian 12 (~1GB RAM) · Nginx wildcard vhost `*.walisantri.com` · PHP 8.4-FPM · PostgreSQL 17 · Redis (≤512MB, Supervisor queue worker) · Let's Encrypt wildcard (Certbot + Cloudflare DNS-01) · Cloudflare Free (WAF/DDoS/wildcard A record) · Cloudflare R2 (zero egress) · UptimeRobot Free.

**Model deploy: host-langsung (bukan kontainer).** Nginx/PHP-FPM/PostgreSQL/Redis berjalan langsung di host — dipilih demi efisiensi resource di VPS ~1GB (Coolify & Docker ditolak karena overhead idle). Environment dijaga reproducible lewat PHP 8.4 di server (Herd lokal pin `^8.3` sesuai `composer.json`, kompatibel) + `setup-server.sh` idempotent yang di-version-control (infra-as-script). Pemicu pindah ke Docker Compose dicatat di §22.

## 6.2 Cloudflare R2

Dua bucket: **`walisantri-storage`** (file app — `exports/{pesantren_id}/`, `imports/{pesantren_id}/`) · **`walisantri-backup`** (DB harian — `daily/` 7h, `weekly/` 30h, `monthly/` 12bln, rotasi via Object Lifecycle Rules). Laravel disk `r2`: driver `s3`, `use_path_style_endpoint: true`, endpoint `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`.

## 6.3 PostgreSQL 17 — Penyesuaian

- Driver: `pgsql` (paket `doctrine/dbal` bila perlu alter kolom). Auth via `scram-sha-256` di `pg_hba.conf` (default modern).
- Tidak ada `unsigned` integer di PostgreSQL — kolom unsigned Laravel dipetakan ke signed `bigint`/`integer`; gunakan `bigInteger()`/`unsignedBigInteger()` (Laravel tetap buat signed). Cukup untuk skala proyek.
- JSON pakai tipe `jsonb` (indexable, lebih efisien dari `json`).
- Enum lewat `CHECK` constraint (default Laravel) agar mudah di-`ALTER` tanpa migrasi tipe native.
- RLS opsional sebagai lapisan isolasi kedua (lihat §1.1) — aktifkan per tabel tenant setelah trait stabil.
- Backup: `pg_dump -Fc` (custom format) → gzip → R2. Restore via `pg_restore`. Aktifkan ekstensi `pgcrypto`/`uuid-ossp` bila dibutuhkan, dan `vector` untuk fitur AI (§20).

## 6.4 CI/CD (GitHub Actions)

Push `main` **atau** `dev` → job `test`: checkout, setup PHP 8.4, `composer install`, jalankan `php artisan test` terhadap service container PostgreSQL 17 (`walisantri_test`) → job `deploy` (SSH ke VPS, hanya jalan bila `test` sukses **dan** `github.ref == 'refs/heads/main'` — push ke `dev` tidak pernah deploy, v4.7): `git pull`, `composer install --no-dev`, `npm ci && npm run build` → `migrate --force`, `config/route/view:cache`, `queue:restart`. Secrets: `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`. Workflow aktif di `.github/workflows/deploy.yml`, sudah diverifikasi sukses end-to-end. Branch flow → §18.

**Branch protection `main` (v4.7):** wajib lewat Pull Request (push langsung diblokir, kecuali admin agar tidak terkunci); wajib status check `Test` lolos & branch up-to-date dengan `main` sebelum merge (`required_status_checks.strict`); **0 approval review wajib** (solo-dev, lihat §22); force-push & delete branch `main` diblokir.

## 6.5 Keamanan Super Admin Panel

`app.walisantri.com/admin` di-IP-whitelist di Nginx untuk membatasi akses fisik ke panel:

```nginx
location /admin { allow 182.x.x.x; deny all; }
```

## 6.6 Observability & Logging (ringan)

Tanpa Prometheus/Grafana/agen-exporter — disesuaikan skala solo-dev & VPS ~1GB. Host-langsung memberi akses proses & file log secara langsung, jadi tooling host-native sudah cukup tanpa lapisan yang memakan RAM:

> **Status implementasi (2026-06-07):** baru `LOG_CHANNEL` aplikasi yang aktif. Sentry, UptimeRobot, GoAccess, dan Laravel Pulse **belum dipasang** — masih rencana, belum ada paket/konfigurasi terkait di `composer.json`.

- **Log aplikasi:** `LOG_CHANNEL=daily` (rotasi harian, retensi terbatas). Rencana: error/eksepsi app diteruskan ke **Sentry** (free tier) untuk alerting & stack trace.
- **Uptime:** rencana pakai **UptimeRobot** Free (lihat §6.1) — ping `app`/situs profil, alert ke WhatsApp/email.
- **Trafik & akses:** rencana pakai **GoAccess** *on-demand* di log Nginx (`goaccess access.log …`) — laporan trafik tanpa daemon berjalan terus-menerus.
- **Resource host:** `htop` (CPU/RAM/proses) + `ncdu` (disk) untuk inspeksi langsung di host — sudah tersedia bawaan Debian.
- **In-app (opsional):** rencana **Laravel Pulse** — dashboard request lambat, queue, & slow query di dalam aplikasi tanpa stack eksternal; nyalakan hanya bila RAM lega.

---

# 7. Filament Panel Structure

Navigasi `app.walisantri.com/admin`:

```
Dashboard                        ← semua role
[Cluster Santri] Users ← top-level sidebar, tanpa group (v4.9, sort 0)
  Santri · Kelas AcademicCap [admin_pesantren] · Kamar Home [admin_pesantren]
  Prestasi Trophy ← admin_pesantren + ustadz (label "Prestasi Santri" → "Prestasi", slug /admin/prestasi)
──
[Cluster Akademik] AcademicCap ← top-level sidebar, tanpa group (v4.9, sort 1)
  Mata Pelajaran [admin_pesantren] · Nilai Akademik
  Ekskul (Master) Trophy [admin_pesantren only, v4.9] · Ekskul Santri UserGroup [admin_pesantren + ustadz, v4.9]
──
[Cluster Tahfidz] BookOpen ← top-level sidebar, tanpa group (v4.7, sort 2) → tab: Setoran · Ujian · Nilai
──
[Cluster Mutabaah] CheckBadge ← top-level sidebar, tanpa group (v4.8, sort 3)
  Mutaba'ah Harian ClipboardDocumentList · Amal Master ListBullet [admin_pesantren only]
──
[Cluster Kesantrian] ShieldCheck ← top-level sidebar, tanpa group (v4.8, sort 4)
  Karakter Rapor Star · Kesehatan Heart [Rintisan+] · Inventaris ArchiveBox [Maju, admin_pesantren + ustadz sejak v4.9]
──
[Cluster Rapor] DocumentChartBar ← top-level sidebar, tanpa group (v4.9, sort 5) → tab: Akademik · Tahfidz · Mutabaah · Karakter
──
── Langganan (group) ── [super_admin only]
  Pesanan Upgrade Banknotes · Kupon Diskon Tag · Pengaturan Harga Cog6Tooth · Rekening Bank BuildingLibrary [v4.11]
──
── Manajemen (group) ──
  Pengguna UserGroup [Admin+SuperAdmin]
  [Cluster Keuangan] Banknotes (v4.9) → Tarif · Tagihan SPP · Saldo Santri · Uang Saku [semua admin_pesantren only]
  Pengumuman SpeakerWave
  [Cluster Pengaturan] Cog6Tooth (v4.9, slug /admin/pengaturan) → Billing · Pengaturan Pesantren
──
Pesantren BuildingOffice2 [SuperAdmin only]
Demo Request [super_admin only] ← masuk di bawah Pesantren
```

> **v4.9 — restrukturisasi navigasi total.** Grup top-level lama "Santri", "Akademik", dan "Keuangan" dibubarkan; semuanya jadi Filament Cluster. `AdminPanelProvider::navigationGroups()` kini hanya mendaftarkan `['Kesantrian', 'Langganan', 'Manajemen']` — nama `Kesantrian` di daftar ini adalah sisa registrasi lama yang sudah tidak dipakai cluster manapun (Cluster Kesantrian sendiri berjalan tanpa group, `$navigationGroup = null`) namun belum dibersihkan dari kode; ini observasi kecil, bukan gap fungsional. Enam Cluster kini top-level tanpa group — urutan render mengikuti `navigationSort` masing-masing: Santri(0) → Akademik(1) → Tahfidz(2) → Mutabaah(3) → Kesantrian(4) → Rapor(5). Grup **Manajemen** berisi campuran Resource biasa (Pengguna, Pengumuman) dan dua Cluster baru (Keuangan sort 2, Pengaturan sort 4).

> **Cluster Tahfidz (v4.7):** 3 resource (Setoran/Ujian/Nilai — sebelumnya `Setoran Tahfidz`/`Ujian Tahfidz`/`Rapor Tahfidz` flat di grup Akademik) digabung jadi satu menu sidebar via `App\Filament\Clusters\Tahfidz`; navigasi antar-resource berupa tab. Filament default merender tab cluster (`SubNavigationPosition::Top`) di bawah header & sebagai dropdown di mobile — di-override via `renderHook(PanelsRenderHook::PAGE_START, …)` di `AdminPanelProvider` (render tab di atas breadcrumbs, ambil halaman aktif via `Livewire::current()`) + CSS (`width:fit-content` agar `margin-inline:auto` bawaan Filament benar-benar men-tengahkan tab, dan sembunyikan dropdown/tab versi default) supaya tampilan konsisten desktop & mobile.

> **Cluster Mutabaah & Kesantrian (v4.8):** "Kesantrian (group)" lama dipecah jadi dua Filament Cluster terpisah — `App\Filament\Clusters\Mutabaah` (Mutaba'ah Harian + Amal Master) dan `App\Filament\Clusters\Kesantrian` (Karakter Rapor + Kesehatan + Inventaris). Pola tab-cluster sama dengan Cluster Tahfidz (render hook + CSS). Pemisahan ini memungkinkan navigasi Amal Master tergabung natural dengan Mutaba'ah Harian tanpa merusak hierarki grup lain.

> **UX panel admin (v4.8):** `sidebarFullyCollapsibleOnDesktop()` aktif — sidebar bisa diciutkan penuh di desktop untuk ruang kerja lebih luas. Bottom navigation mobile ditambahkan via render hook `BODY_END` → view `filament.admin.bottom-nav` (shortcut ke Dashboard, Santri, Mutabaah, dan halaman sering dipakai).

> **Cluster Santri, Akademik, Rapor, Keuangan, Pengaturan (v4.9):** perluasan pola cluster yang sama dipakai sejak Tahfidz (v4.7) dan Mutabaah/Kesantrian (v4.8) — render hook + CSS tab identik dipakai ulang tanpa modifikasi. `App\Filament\Clusters\Santri` & `App\Filament\Clusters\Akademik` mengangkat grup lama jadi cluster top-level (menambahkan Ekskul Master & Ekskul Santri ke Akademik). `App\Filament\Clusters\Rapor` menggabungkan 4 halaman laporan (Rapor Akademik dipindah keluar dari Cluster Akademik ke sini) sebagai custom Page dengan tab Akademik → Tahfidz → Mutabaah → Karakter. `App\Filament\Clusters\Keuangan` (dalam grup Manajemen) menaungi Tarif SPP, Tagihan SPP, Saldo Uang Saku, dan Uang Saku. `App\Filament\Clusters\PengaturanPesantren` (slug `/admin/pengaturan`, dalam grup Manajemen) menggabungkan `BillingPage` dan `PesantrenSettingsPage`.

> Kelas & Kamar hanya tampil untuk `admin_pesantren` (bukan ustadz). Ustadz hanya melihat data santri binaannya di semua menu Kesantrian; sejak v4.9 ustadz juga bisa create/edit Inventaris santri binaannya (sebelumnya hanya bisa melihat) dan create/edit Ekskul Santri. TarifSpp, TagihanSpp, SaldoUangSaku, dan UangSaku hanya `admin_pesantren` + `super_admin` (bukan ustadz). Ekskul Master hanya `admin_pesantren`.

**Filament v5 notes:** Form/Infolist/Table di file terpisah · `Section` dari `Filament\Schemas\Components\Section` · `$navigationGroup` bertipe `string|UnitEnum|null` (bukan `?string`), `use UnitEnum;` wajib.

---

# 8. Portal Wali Santri

Blade + TailwindCSS murni (tanpa Flux UI), mobile-first. Akses via Magic Link (§4.3, jalur utama — klik langsung masuk read-only) atau login ber-brand `app.walisantri.com/login?tenant={slug}` yang dicapai dari tombol "Portal Wali Santri" di situs profil pesantren (§1.3).

**Bottom nav wali (v4.9):** Beranda · SPP · Pengumuman · Uang Saku · Rapor. Tidak ada tab "Santri" terpisah — "Beranda" merangkap fungsi navigasi ke detail santri (`wali.santri.show`).

**Fitur MVP (selesai v4.4):**
- **Dashboard:** sapaan + pengumuman pondok terkini; alert jika ada santri dalam kondisi Rujukan_Luar/Istirahat_Total; banner notifikasi tunggakan SPP (orange, tap ke halaman SPP). **Branching (v4.8):** jika wali memiliki tepat 1 anak aktif → langsung tampil halaman detail penuh (capaian juz, persentase amalan, status kesehatan, rapor terakhir via `SantriDetailPresenter`); jika >1 anak → tampil cards ringkasan per anak dengan tap ke detail masing-masing.
- **Statistik Tahfidz:** grafik perkembangan hafalan, riwayat setoran, nilai kelancaran.
- **Statistik Kesehatan:** tren berat/tinggi badan, riwayat rekam medis.
- **Detail Mutaba'ah Harian:** tabel amalan harian per santri dengan filter tanggal.
- **Detail Santri:** termasuk seksi Prestasi (daftar prestasi dengan badge medal tingkat) dan seksi **Ekstrakurikuler** (v4.9, daftar ekskul aktif santri + level).
- **Daftar Inventaris santri** (v4.9, selesai — sebelumnya roadmap): `InventarisController::show()` + view `wali/santri/inventaris.blade.php`, daftar barang & kondisi milik santri, baca-saja.
- **Halaman SPP (`/wali/spp`):** ringkasan tunggakan per santri (status, nominal, jatuh tempo); info rekening bank pesantren; tombol "Saya Sudah Transfer" → form upload foto bukti → status berubah ke `menunggu_konfirmasi`. Tab di bottom nav wali.
- **Halaman Uang Saku (`/wali/uang-saku` + `/wali/uang-saku/{santri}`)** *(v4.9)*: ringkasan saldo (akumulasi setoran − pengambilan) & riwayat transaksi uang saku per santri, baca-saja. Tab di bottom nav wali.
- **Halaman Rapor (`/wali/rapor`):** filter santri + tahun ajaran, dua tab — "📖 Tahfidz" (nilai per periode & rekomendasi) dan "🌱 Karakter" (penilaian adab 7 item, kepribadian 9 item, catatan ustadz); tombol ekspor PDF siap cetak (`LaporanController::exportPdf`, route `wali.laporan.pdf`, via `barryvdh/laravel-dompdf`). Tab di bottom nav wali.

**Fitur roadmap (post v1.0):**
- Kalender Amalan Harian (warna: hijau lengkap / kuning sebagian / abu udzur / merah alpa) — tampilan kalender interaktif.

---

# 9. Keamanan Aplikasi

## 9.1 Password Reset

**Admin & Ustadz (email):** klik Lupa Password di Filament → email link reset (token 60 menit, single-use) → set password baru. *(Dev: Mailpit via Herd; prod: SMTP Relay, `.env MAIL_*`.)*

**Wali Santri (WhatsApp OTP):** klik Lupa Password di `app.walisantri.com/login`, masukkan nomor → cek `users` role `wali_santri` → dispatch OTP 6 digit ke queue `whatsapp-notif`, simpan Redis `otp:{phone_number}` TTL 10 menit → validasi OTP + password baru, hapus cache. *Rate limit: max 3 OTP/nomor/jam (`RateLimiter`).*

## 9.2 Rate Limit & Brute Force

| Endpoint | Limit | Lockout |
|---|---|---|
| `app.../login` | 5/menit/IP | Blokir 15 menit |
| `app.../admin` | IP whitelist Nginx | Ditolak di server |
| `/check-slug/{slug}` | 30/menit/IP | HTTP 429 |
| `/wali/reset-otp` | 3/jam/nomor | HTTP 429 |

## 9.3 Custom Error Pages (`resources/views/errors/`)

`404` subdomain/halaman tidak ada · `403` Magic Link coba non-GET · `422` kuota penuh (pesan upgrade) · `429` rate limit (countdown) · `503` maintenance (estimasi).

---

# 10. Audit Log & Activity Tracking

## 10.1 `activity_logs` (DB Central, append-only)

`id` PK · `pesantren_id` FK null (null = aksi Super Admin) · `user_id` FK→users · `event` · `auditable_type` · `auditable_id` · `old_values`/`new_values` jsonb null · `ip_address`/`user_agent` null · `created_at`. Tidak ada UPDATE/DELETE (via Observer). Ditampilkan sebagai tab Riwayat Aktivitas di detail Santri/User/Pesantren.

## 10.2 Event Diaudit

`santri.created` · `santri.deleted` · `santri.uuid_regenerated` · `user.role_changed` · `user.password_reset` · `pesantren.suspended` · `pesantren.activated` · `pesantren.paket_changed` · `pesantren.slug_changed` · `magic_link.viewed` (v4.9, koreksi dari `magic_link.sent` — dicatat saat modal aksi "Kirim Magic Link" dibuka di Filament, bukan saat WhatsApp benar-benar terkirim) · `export.generated`.

## 10.3 Retention

Log operasional 2 tahun · log billing/paket 5 tahun · purge otomatis via Scheduler tiap tanggal 1.

---

# 11. Scheduled Tasks (Laravel Scheduler)

Didefinisikan via `Schedule` di `AppServiceProvider`. Notifikasi WhatsApp ke wali **tidak** dijadwalkan — selalu manual via Filament.

| Job | Jadwal | Keterangan |
|---|---|---|
| `CheckExpiredTenants` | Harian 00.01 | Update `status_berlangganan` lewat `expired_at` |
| `WarnExpiringTenants` | Harian 09.00 | Email peringatan admin 7 & 3 hari sebelum expired |
| `PurgeAuditLogs` | Tanggal 1 | Hapus log sesuai retention |
| `DatabaseBackup` | Harian 02.00 | `pg_dump -Fc` → gzip → R2 `walisantri-backup/daily/` |
| `WarmDashboardCache` | Tiap 25 menit | Pre-generate cache dashboard wali (santri aktif) |
| `PruneStaleCache` | Harian 03.00 | Hapus cache Redis santri non-aktif |

> `CheckExpiredTenants` & `WarnExpiringTenants` hanya query DB central, tidak melewati koneksi tenant — tidak boleh terpengaruh `SaaSLifecycleLock`.

---

# 12. Notifikasi WhatsApp

On-demand penuh — tidak ada pengiriman terjadwal otomatis. Gateway Fonnte/Waba (`.env WHATSAPP_GATEWAY_*`), via job `KirimNotifikasiWhatsapp` di queue `whatsapp-notif` (Redis). Pengiriman per-santri = dispatch langsung dari aksi Filament; massal per kamar = loop + delay antar job; retry max 3× exponential backoff, gagal permanen → `failed_jobs`.

| Trigger | Aktor | Konten |
|---|---|---|
| Magic Link per santri / massal per kamar | Admin/Ustadz | Link portal + nama santri |
| Rapor baru | Admin/Ustadz | Notif rapor + Magic Link |
| Santri `Rujukan_Luar` | Ustadz | Kondisi santri + Magic Link rekam medis |
| Pengumuman penting | Admin | Isi maklumat + link |
| Reset OTP | System | OTP 6 digit |

---

# 13. Kebijakan Data & Retensi

## 13.1 Retensi per Status Tenant

| Status | Data | Tindakan |
|---|---|---|
| Trial expired tanpa bayar | Tersimpan | Grace period 7 hari akses terbatas → suspended |
| Suspended ≤ 90 hari | Tersimpan, tak bisa diakses | Admin bisa reaktivasi kapanpun |
| Suspended > 90 hari | Dijadwalkan hapus | Email peringatan 30 hari sebelum hapus |
| Hapus permanen | Dihapus | Termasuk file R2 |

## 13.2 Data Sensitif Anak

Rekam medis & karakter hanya untuk pesantren + wali terkait, tidak pernah lintas tenant (dijamin `pesantren_id` + Global Scope + RLS). Audit log mencatat akses/perubahan. Backup R2 enkripsi at-rest otomatis.

## 13.3 Hak Penghapusan

Admin ajukan penghapusan permanen ke Super Admin via email → diproses ≤7 hari kerja → data dihapus dari DB & R2.

---

# 14. Onboarding UX & Empty State

**Setup checklist** (status di `onboarding_completed_steps` jsonb, di-update Observer): (1) profil pesantren (alamat, logo); (2) ustadz pertama; (3) santri pertama / import massal; (4) Magic Link wali pertama; (5) pengumuman perdana (opsional).

**Empty state:** Santri kosong → "tambah santri / import" · Tahfidz → "mulai input setoran" · Mutaba'ah → "gunakan Grid Input per kamar" · Portal Wali santri baru → "data sedang dipersiapkan, cek besok".

---

# 15. Export Data

| Modul | Format | Aktor | Catatan |
|---|---|---|---|
| Rekap Mutaba'ah Bulanan | Excel | Admin/Ustadz | Per santri/kamar, filter bulan |
| Rapor Akademik / Tahfidz / Mutabaah / Karakter | PDF | Admin/Ustadz | Layout siap cetak per santri, via Cluster Rapor (v4.9, lihat §7) |
| Data Santri | Excel | Admin | Semua santri aktif (arsip) |
| Rekam Medis Periode | Excel | Admin/Ustadz | Filter tanggal, semua paket (v4.9: batasan "Berkembang+" dikoreksi — tidak ada Gate paket di kode) |
| Rekap Inventaris | Excel | Admin | Status barang seluruh santri |

**Alur:** klik Export + filter → dispatch job `ExportData` ke queue `bulk-import` → generate di server, simpan R2 `exports/{pesantren_id}/` → notif Filament + link download → file auto-hapus 24 jam (lifecycle rule). PDF: Laravel-DomPDF; Excel: Laravel Excel (Maatwebsite) — keduanya tambah ke `composer.json`.

---

# 16. Upgrade & Downgrade Paket

## 16.1 Alur Pembayaran Manual (Order & Invoice) *(v4.11 — sebelumnya belum terdokumentasi)*

Admin pilih paket & durasi di `UpgradePage` → `UpgradeOrderService::createOrder()` hitung harga via `BillingCalculatorService`, buat baris `orders` (status `pending_payment`) + `invoices` terkait → redirect ke `OrderInvoicePage` (`/admin/order-invoice-page?order={id}`). Halaman ini menampilkan detail order (tabel harga/kuota/durasi) dan section **"Cara Pembayaran"**: daftar rekening bank platform aktif dari `platform_bank_accounts` (§3.1), masing-masing dengan logo (bila diunggah) dan tombol **"Salin"** nomor rekening. Admin transfer manual lalu upload bukti transfer (disk `local`, validasi mime server-side) → status order berubah `awaiting_confirmation`. Super Admin review bukti di `OrderResource` → konfirmasi (`UpgradeOrderService::confirmOrder()`, update `pesantrens.paket_langganan`/`max_santri_kuota`/`expired_at`) atau tolak (`rejectOrder()`, dengan catatan). Tidak ada payment gateway otomatis — seluruh alur manual by design (konsisten dengan alur SPP wali santri di §3.2, sama-sama transfer manual + verifikasi admin).

**Upgrade:** Admin ajukan di `/billing` → Super Admin verifikasi bayar, update `paket_langganan` & `max_santri_kuota` di panel admin → Gate otomatis update, modul baru langsung aktif tanpa logout.

**Kebijakan durasi saat upgrade:** Sisa masa aktif lama **dipertahankan** sebagai titik awal (`expired_at` lama), durasi baru ditambahkan di atasnya — paket langsung berganti saat konfirmasi. Tidak ada proration. Contoh: Rintisan 12 bulan aktif + upgrade Tumbuh 12 bulan = tenant mendapat Tumbuh selama 24 bulan ke depan. Ini disengaja — mendorong upgrade lebih awal tanpa membuat tenant merasa kehilangan sisa langganan. Untuk mencegah pembelian durasi terlalu pendek saat sisa aktif masih panjang, berlaku batas minimum:

| Sisa masa aktif | Minimum durasi upgrade |
|---|---|
| ≤ 6 bulan | Bebas (1, 3, 6, atau 12 bulan) |
| > 6 bulan s.d. 9 bulan | Minimum 6 bulan |
| > 9 bulan | Hanya 12 bulan |

Validasi dilakukan di dua lapisan: opsi durasi yang tidak memenuhi syarat disembunyikan dari `UpgradePage`, dan `abort_if` di `prosesPembayaran()` sebagai lapisan kedua.

**Downgrade:** Maju→Berkembang kunci Inventaris & AI · Berkembang→Rintisan kunci Kesehatan · santri aktif > kuota baru → downgrade diblokir (nonaktifkan santri dulu). Data modul terkunci tidak dihapus — pulih saat upgrade kembali.

---

# 17. Testing Strategy

Pendekatan **Unit Test**, fokus lapisan kritis: isolasi tenant, business logic middleware, service layer. Jalan lokal sebelum push + otomatis di GitHub Actions sebelum deploy. Deploy hanya jalan jika `php artisan test` sukses (job `test` di `deploy.yml`, terhadap PostgreSQL — `paratest`/`--parallel` tidak dipakai karena migrasi bergantung fitur khusus Postgres yang tidak aman dijalankan paralel pada DB bersama). Target coverage tidak per-persentase; wajib: semua test `TenantIsolation/` & `Middleware/` lulus 100%.

**Prioritas wajib sebelum go-live:**
- *Tenant isolation:* santri/tahfidz/mutaba'ah/kesehatan/inventaris terisolasi per `pesantren_id`; Super Admin bisa lintas tenant via `withoutGlobalScope`; wali hanya akses anaknya. (Bila RLS aktif, tambahkan test policy di level DB.)
- *Middleware:* `CheckTenantQuota` (422 saat penuh) · `SaaSLifecycleLock` (redirect/blokir) · `VerifyMagicToken` (read-only UUID valid, 404 invalid, 403 non-GET) · `PublicTenantResolver` (resolve host ke `tenant_domains`, 404 invalid) · resolusi tenant dari akun saat login (email → `pesantren_id`).
- *Service & rules:* `BillingCalculatorService` (formula kuota custom Maju, X=0 di-cover) · `SlugNotReserved` · `ValidTenantSlug` (format/panjang/unik) · `OnboardPesantren` (buat pesantren+admin, paket rintisan, trial 30 hari).
- *Model & observer:* `HasUuids` isi `uuid` saja · `SoftDeletes` Santri · Observer Kesehatan auto-udzur · Multi-Anak Logic.

**Konfigurasi:** unit test pakai PostgreSQL ephemeral (mis. service container `postgres` di GitHub Actions) atau SQLite in-memory untuk test yang tidak bergantung fitur PostgreSQL; `CACHE_DRIVER=array`, `QUEUE_CONNECTION=sync`. Test isolasi tenant & RLS **wajib** pakai PostgreSQL (bukan SQLite) agar policy ikut teruji.

```
tests/Unit/{Services,Rules,Models,Middleware,Observers}/...
tests/TenantIsolation/DataIsolationTest.php   ← wajib lulus sebelum go-live (PostgreSQL)
```

---

# 18. Staging Environment

> ⚠️ **Status (2026-06-07): belum dibuat.** Tabel di bawah adalah desain target/roadmap — saat ini hanya ada satu environment (production), deploy langsung dari push ke `main`. Belum ada domain `staging.*`, DB staging, branch `develop`, maupun kredensial WhatsApp/email terpisah.

| Komponen | Production | Staging (target, belum dibuat) |
|---|---|---|
| Domain | `walisantri.com` / `*.walisantri.com` | `staging.walisantri.com` |
| VPS / DB / R2 | ~1GB · `walisantri_db` · `walisantri-storage` | 2GB · `walisantri_staging` · `walisantri-staging` |
| WhatsApp / Mail | Token prod / SMTP Relay | Token staging / Mailtrap |
| `APP_DEBUG` / Deploy | `false` / push `main` | `true` / push `develop` |

> ⚠️ Staging **wajib** kredensial WhatsApp & email terpisah — tanpa ini testing mengirim pesan nyata ke wali sungguhan.

**Branch flow saat ini (tanpa staging, v4.7):** `dev` (kerja & push bebas — CI hanya jalankan job `test`, tidak ada deploy) → buka PR ke `main` (wajib status check `Test` lolos + branch protection, lihat §6.4) → merge → auto-deploy production.

**Branch flow target (setelah staging dibuat):** `feature/*` (Herd lokal, tanpa deploy) → `develop` (auto-deploy staging) → `main` (auto-deploy production).

---

# 19. Disaster Recovery

**Target restore:** app crash <5 menit (Supervisor restart) · deploy rusak <15 menit (`git checkout`) · data terhapus <1 jam (restore R2) · VPS mati <4 jam (provisioning baru + restore).

**Runbook 1 — Rollback deploy:**
```bash
cd /var/www/walisantri && git log --oneline -5 && git checkout {commit_hash}
composer install --no-dev --optimize-autoloader
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan queue:restart
```

**Runbook 2 — Restore DB dari R2** (`bash /opt/scripts/restore-db.sh [tanggal]`): cari backup di `walisantri-backup/daily/` → download `.dump.gz` → `php artisan down` → `gunzip` lalu `pg_restore --clean --if-exists -d walisantri_db` → `php artisan migrate --force` → clear cache → `php artisan up` → hapus `/tmp`. *Verifikasi manual sebelum umumkan normal.*

**Runbook 3 — VPS mati total (~1 jam):** (1) provisioning VPS Debian 12 (~1GB RAM), catat IP; (2) update A record Cloudflare `*` & `@`; (3) jalankan `setup-server.sh` (Nginx, PHP 8.4, PostgreSQL 17, Redis, Certbot, Supervisor); (4) clone repo, `.env.production`, `composer install`, `npm build`, `key:generate`; (5) `restore-db.sh` backup terakhir; (6) verifikasi login semua role + queue. Simpan `EMERGENCY.md` di GitHub, Google Drive, & Notes HP.

**Verifikasi backup bulanan (~30 menit):** `restore-db.sh` ke DB staging → cek jumlah pesantren/santri/record → catat di `BACKUP_LOG.md` → hapus DB staging.

**Eskalasi:** down <30mnt → restart via SSH · down >30mnt → Runbook 3 + info mitra · data korup → Runbook 2 + maintenance mode · breach → suspend semua tenant + ganti credential.

---

# 20. Fitur AI (Post v1.0)

Opsional, setelah MVP. Hanya paket Maju. Laravel 13 AI SDK (first-party). **Ringkasan Perkembangan Santri:** narasi otomatis dari mutaba'ah + tahfidz via `Ai::text()->generate()`. **Deteksi Pola Ketidakhadiran:** embeddings disimpan & dicari via ekstensi **pgvector** (`CREATE EXTENSION vector`; kolom `vector`, index HNSW/IVFFlat) untuk anomali pola udzur sebagai early warning — native di PostgreSQL, tanpa datastore vektor terpisah.

---

# 21. Model Bisnis & Bagi Hasil

**Anggaran operasional/bulan (MVP):** VPS Rp 250rb · WhatsApp Gateway Rp 150rb · Email Rp 60rb · Domain & SSL Rp 30rb · R2 Rp 0–15rb · Pemasaran Rp 350rb → **Total Rp 840–855rb**.

**Bagi hasil 50:50:** Faza (Developer — full-stack, server, keamanan, maintenance) · Mitra Bisnis (Marketing — penetrasi pasar, presentasi, support, feedback lapangan).

**Simulasi (ilustratif, 11 klien berbayar):** 3 Rintisan (3 × 150rb = 450rb) + 4 Tumbuh (4 × 299rb = 1.196rb) + 2 Berkembang (2 × 350rb = 700rb) + 2 Maju (2 × 750rb = 1.500rb) = **Gross Rp 3.846rb** − operasional 840rb = **Net Rp 3.006rb** → masing-masing Rp 1.503rb. *(Tidak ada tier Gratis — konversi digerakkan via trial 30 hari. Paket Tumbuh diasumsikan jadi mayoritas karena posisinya sebagai paket paling populer.)*

**Target milestone klien (anchor perencanaan):**

| Milestone | Klien berbayar | Asumsi mix rata-rata | Net/bulan |
|---|---|---|---|
| Break-even operasional | ~6 klien | Rp 300rb/klien rata-rata | Menutup biaya operasional |
| Bagi hasil layak (≥ UMR/orang) | ~35 klien | Rp 300rb/klien rata-rata | ~Rp 2,4jt/orang |
| Target 12 bulan pertama | **20 klien berbayar** | — | Anchor marketing mitra |

> *Target 20 klien berbayar di 12 bulan pertama adalah anchor perencanaan — bukan jaminan, tapi angka konkret untuk mengukur apakah strategi marketing berjalan. Revisi bersama mitra bisnis setiap kuartal.*

---

# 22. Catatan Implementasi Aktual

**Versi:** Laravel 13.11.1 · Filament v5.6.3 · PHP 8.3 (Herd, dev) / PHP 8.4-FPM (VPS produksi — `composer.json` tetap `^8.3`, kompatibel) · PostgreSQL 17 · R2 (belum dikonfigurasi, lihat §6.2) · SSL Wildcard DNS-01 · deploy GitHub Actions (terverifikasi sukses 2026-06-07) · subdomain aktif kembali. PRD ini adalah v4.12 (file: `docs/walisantri-prd-v4.md`). **Model bisnis terkini:** tidak ada paket Gratis — `PaketLangganan` enum `rintisan`/`tumbuh`/`berkembang`/`maju`; onboarding mulai dengan trial Rintisan 30 hari. Lifecycle: `trial` → `expired` → (+7 hari) `suspended`. Maju base price Rp 750k/bulan untuk 1.000 santri (X=0). Paket Tumbuh (250 santri, Rp 299k) adalah paket paling populer. Minimum durasi upgrade dibatasi berdasarkan sisa masa aktif (lihat §16).

**Bug & fix:** `HasUuids` isi `id` jika tak di-override → `uniqueIds(): ['uuid']` · `$navigationGroup` `?string` error → `string|UnitEnum|null` · index name >63 char (batas PostgreSQL) → nama eksplisit pendek · ingat PostgreSQL tak punya unsigned int (kolom unsigned → signed bigint) · (v4.7) `tahun_ajaran` di form Nilai Akademik/Rapor Tahfidz semula `TextInput` bebas → mismatch format antar input & filter rapor bikin data tidak muncul → diganti `Select` dropdown seragam (service `TahunAjaranOptions`) · (v4.7) Filament cluster default merender sub-navigation tab di bawah header & dropdown khusus mobile → di-override via render hook + CSS agar tab tampil di atas breadcrumbs, konsisten desktop/mobile (detail di §7).

**Di-skip (post v1.0):** WhatsApp Gateway + Queue Job · Feature test isolasi & middleware · PostgreSQL RLS policy per tabel · zero-downtime deploy · migrasi schema-per-tenant (setelah >50 tenant) · Kalender Amalan Harian interaktif (warna). *(v4.9: "Excel Importer massal" dan "Daftar Inventaris santri" dipindah keluar dari daftar ini — sudah selesai, lihat §3.2/§22 changelog dan §8.)*

**Catatan skema periode (v4.9):** kolom `bulan` kini konsisten ditambahkan ke tiga tabel berbasis periode — `nilai_akademik`, `kesantrian_karakter_rapor`, `tahfidz_rapor` — mendampingi `tahun_ajaran`/`periode` yang sudah ada. Pola ini jadi referensi saat modul periode lain ditambah ke depan.

**Batas yang Diketahui (keputusan sadar yang ditunda, dengan pemicu peninjauan):**

| Batas | Kondisi sekarang | Pemicu tinjau ulang |
|---|---|---|
| `users` mencampur staf & wali (dibedakan `role`) | Hemat untuk MVP; atribut staf vs wali belum dipisah | Saat **modul SDM/kepegawaian** masuk (gaji, jadwal mengajar, sertifikasi) → pertimbangkan pecah ke tabel profil `staff`/`wali` |
| `kelas` & `kamar` sudah jadi entitas master (v4.3) | Tabel `kelas` + `kamar` per-tenant, santri FK ke keduanya | Saat butuh atribut lebih lanjut per-kelas/kamar (kapasitas, PJ, jadwal) → tambah kolom ke tabel yang sudah ada |
| Sebagian enum di-hardcode (CHECK constraint) | Aman untuk nilai tetap (`A/B/C/D`, `tipe_setoran`) | Saat pesantren minta **menambah kategori** (mis. `kategori_keluhan`, jenis amalan) → migrasi ke tabel `master_{x}` per-tenant |
| Sebagian besar entitas tenant menggantung ke `santri` | Pola per-santri konsisten & teruji; SPP & **akademik formal** (`mata_pelajaran` — akar `kelas`, bukan `santri`, v4.5) sudah jadi contoh nyata "modul bukan-per-santri" yang ikut §1.7 | Saat modul bukan-per-santri lain masuk (mis. aset pondok, kepegawaian) → ikuti pola yang sama: entitas baru dengan akar selain `santri`, ikuti §1.7 |
| Email unik global | Wali tak bisa pakai email sama di dua pesantren | Bila kasus ini sering → pertimbangkan identitas wali lintas-tenant (kompleks; kemungkinan tetap ditolak) |
| Deploy host-langsung (tanpa Docker) | Ramping & cocok skala MVP solo-dev di VPS ~1GB; environment dijaga via PHP 8.4 di server + `setup-server.sh` idempotent | Saat (a) butuh service berat di-install native (mis. Meilisearch, runtime AI), (b) pindah multi-server / DB-per-tenant, atau (c) ada dev kedua (parity environment baru terbayar) → pindah ke **Docker Compose** (tanpa Coolify) |

> *Filosofi: batas-batas ini **sengaja** dipilih demi kesederhanaan MVP solo-dev. Yang penting bukan menghindarinya, tapi menamainya sekarang agar saat pemicunya datang, ia ditangani sebagai keputusan terencana — bukan kejutan.*

---

# 23. Instruction for Claude AI Development

1. Laravel 13 (PHP 8.3+) + Filament v5. Migrasi sesuai §3, FK + composite index wajib, SoftDeletes pada `Santri`, pisahkan ke `migrations/central/` & `migrations/tenant/`. DB driver `pgsql`.
2. Trait `Multitenantable`: Global Scope + auto-assign `pesantren_id` saat `creating`. Override `uniqueIds()` pada model `HasUuids` agar hanya isi `uuid`.
3. Filament v5: Form/Infolist/Table di file terpisah. `Section` dari `Filament\Schemas\Components\Section`. `$navigationGroup` bertipe `string|UnitEnum|null`.
4. Middleware `CheckTenantQuota`, `SaaSLifecycleLock`, `VerifyMagicToken`, `PublicTenantResolver` sesuai §1 & §5. Daftar alias di `bootstrap/app.php`.
5. Queue Routing terpusat di `AppServiceProvider` via `Queue::route()`, cek `class_exists()` dulu.
6. Portal Wali: Blade + TailwindCSS murni, mobile-first. Akses via login terpusat `app.walisantri.com/login` (tenant dari akun) atau Magic Link via `VerifyMagicToken`. URL Magic Link pakai host tetap `app.walisantri.com/report/{uuid}`.
7. Dashboard Central: Filament Widgets di `app.walisantri.com/admin` (panel yang sama, menu difilter `canAccess()`/`canView()` per role), `canView()` hanya `super_admin`. Stats dari `santri_count_cache`, bukan `COUNT()` realtime.
8. Login terpusat di `app.walisantri.com`: resolve tenant dari akun (email unik global → `pesantren_id`), inject `current_pesantren` (+ `SET app.current_pesantren` bila RLS aktif). Host publik (`{slug}.walisantri.com`/custom domain): `PublicTenantResolver` cocokkan `getHost()` ke `tenant_domains` → `pesantren_id` (read-only, hanya situs profil). Slug **mutable** + cooldown 90 hari (tabel `slug_releases`); reserved via `SlugNotReserved`; tiap ubah → audit `pesantren.slug_changed`.
9. File storage R2 via disk `r2` (`s3`, `use_path_style_endpoint: true`). Backup PostgreSQL harian via cron + AWS CLI ke `walisantri-backup`. Lifecycle rules di Cloudflare untuk rotasi.
10. PostgreSQL 17: driver `pgsql`, auth `scram-sha-256`, JSON pakai `jsonb`, enum via `CHECK` constraint, ingat tak ada unsigned int. Backup `pg_dump -Fc`, restore `pg_restore`. Ekstensi `vector` untuk AI (§20), RLS opsional untuk isolasi tenant (§1.1).
11. Unit test isolasi tenant (`tests/TenantIsolation/DataIsolationTest.php`) wajib lulus sebelum deploy — pakai PostgreSQL (bukan SQLite) agar RLS/policy teruji. Seluruh suite (`Unit`, `Feature`, `TenantIsolation`) dijalankan terhadap PostgreSQL di CI (lihat §6.4); Deploy GitHub Actions hanya jika `php artisan test` sukses.
12. Staging: `.env` terpisah dengan `WHATSAPP_GATEWAY_TOKEN` & `MAIL_*` berbeda dari production. Jangan pernah pakai token production di staging.

---

*Confidential — Internal Document | Walisantri.com v4.12 | Juli 2026*
