<?php

namespace App\Exports;

use App\Enums\PaketLangganan;
use App\Enums\StatusBerlangganan;
use App\Enums\UserRole;
use App\Models\Pesantren;
use App\Support\Waktu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Ekspor seluruh pesantren terdaftar untuk super admin.
 *
 * Querynya datang dari `getTableQueryForExport()` milik halaman Filament
 * supaya filter/pencarian/urutan tabel ikut terbawa — termasuk TernaryFilter
 * `is_demo` yang secara bawaan menyembunyikan tenant sandbox, sehingga berkas
 * standarnya terbaca sebagai daftar pelanggan.
 */
class DataPesantrenExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    private Builder $query;

    public function __construct(Builder $query)
    {
        // Semua agregat dipasang SEKALI di sini, bukan dihitung per baris di
        // map(): satu subquery per kolom mengalahkan satu query per pesantren.
        //
        // santri: pakai withCount, JANGAN santri_count_cache. Kolom cache itu
        // hanya pernah ditulis saat onboarding (diset 0) dan saat refresh
        // sandbox — observer pemeliharanya tidak pernah dibuat, jadi nilainya
        // nyaris selalu 0 dan berbohong. Global scope tenant `Multitenantable`
        // no-op untuk super_admin, dan SoftDeletes mengecualikan santri
        // terhapus; keduanya perilaku yang benar untuk "santri aktif".
        //
        // orderBy('id'): tiebreaker deterministik untuk Builder::chunk() milik
        // maatwebsite — sortir bawaan tabel `created_at desc` tidak unik. Query
        // dari getTableQueryForExport() sudah dibubuhi tiebreaker id oleh
        // Filament sendiri sehingga di sana baris ini no-op; yang dijaganya
        // adalah pemakaian langsung kelas ini tanpa query tabel.
        $this->query = $query
            ->with('adminUtama')
            ->withCount([
                'users as jumlah_admin' => fn (Builder $q) => $q->where('role', UserRole::AdminPesantren->value),
                'users as jumlah_ustadz' => fn (Builder $q) => $q->where('role', UserRole::Ustadz->value),
                'users as jumlah_wali' => fn (Builder $q) => $q->where('role', UserRole::WaliSantri->value),
                'santri as santri_aktif' => fn (Builder $q) => $q->where('status_aktif', true),
            ])
            ->withMax('santri as santri_terakhir', 'created_at')
            ->orderBy('id');
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Pesantren',
            'Slug',
            'Tenant Demo',
            'Tanggal Terdaftar',
            'Paket',
            'Status Langganan',
            'Kuota Santri',
            'Santri Aktif',
            'Sisa Kuota',
            'Expired',
            'Sisa Hari Aktif',
            'Nama Admin',
            'Email Admin',
            'No. HP Admin',
            'Email Admin Terverifikasi',
            'Jumlah Admin',
            'Jumlah Ustadz',
            'Jumlah Wali Santri',
            'Telepon Pesantren',
            'Email Kontak Pesantren',
            'Kota',
            'Provinsi',
            'Alamat Lengkap',
            'Onboarding Selesai',
            'Santri Terakhir Ditambahkan',
        ];
    }

    /**
     * @param  Pesantren  $record
     */
    public function map($record): array
    {
        $admin = $record->adminUtama;
        $santriAktif = (int) ($record->santri_aktif ?? 0);
        $wilayah = $record->profil['wilayah'] ?? [];

        return [
            $record->id,
            $record->nama_pesantren,
            $record->slug,
            $record->is_demo ? 'Ya' : 'Tidak',
            self::waktu($record->created_at),

            // paket_langganan & status_berlangganan TIDAK di-cast enum di model
            // Pesantren (beda dengan Order), jadi tetap lewat tryFrom().
            PaketLangganan::tryFrom((string) $record->paket_langganan)?->label() ?? $record->paket_langganan,
            StatusBerlangganan::tryFrom((string) $record->status_berlangganan)?->label() ?? $record->status_berlangganan,

            $record->max_santri_kuota,
            $santriAktif,
            max(0, (int) $record->max_santri_kuota - $santriAktif),
            self::waktu($record->expired_at, jam: false),
            self::sisaHari($record->expired_at),

            $admin?->name ?: '—',
            $admin?->email ?: '—',
            $admin?->phone_number ?: '—',

            // Diturunkan dari adminUtama, BUKAN dari kolom virtual
            // `admin_terverifikasi` milik PesantrensTable. Yang di tabel berarti
            // "ada admin mana pun yang terverifikasi"; di sini yang ditanyakan
            // adalah admin yang namanya tertulis di tiga kolom sebelah kiri.
            $admin === null ? '—' : ($admin->email_verified_at !== null ? 'Ya' : 'Tidak'),

            (int) ($record->jumlah_admin ?? 0),
            (int) ($record->jumlah_ustadz ?? 0),
            (int) ($record->jumlah_wali ?? 0),

            $record->profil['telepon'] ?? '—',
            $record->profil['email_kontak'] ?? '—',
            $wilayah['kota']['nama'] ?? '—',
            $wilayah['provinsi']['nama'] ?? '—',
            $record->alamatLengkap() ?: '—',

            $record->isOnboardingComplete() ? 'Ya' : 'Belum',

            // Pengganti "aktivitas terakhir", yang tidak bisa dijawab jujur oleh
            // data yang ada: activity_logs cuma mencatat enam event siklus
            // langganan (hasilnya hampir selalu = tanggal daftar), dan
            // sessions.last_activity di-garbage-collect sehingga kosong bukan
            // berarti tidak aktif. Tanggal santri terakhir masuk adalah sinyal
            // nyata bahwa tenant ini benar-benar dipakai.
            self::waktu($record->santri_terakhir),
        ];
    }

    public function title(): string
    {
        return 'Data Pesantren';
    }

    /**
     * Timestamp disimpan UTC, seluruh pengguna platform di WIB (§ App\Support\Waktu).
     */
    private static function waktu(mixed $waktu, bool $jam = true): string
    {
        if (blank($waktu)) {
            return '—';
        }

        $carbon = $waktu instanceof \DateTimeInterface
            ? Carbon::instance($waktu)
            : Carbon::parse((string) $waktu);

        return $carbon->timezone(Waktu::zona())->format($jam ? 'd/m/Y H:i' : 'd/m/Y');
    }

    /**
     * Sisa hari sampai langganan habis, dihitung menurut kalender WIB.
     * Negatif berarti sudah lewat — sengaja tidak di-clamp ke 0: super admin
     * perlu tahu SEBERAPA lama sebuah tenant menunggak, bukan sekadar bahwa ia
     * menunggak.
     */
    private static function sisaHari(mixed $expiredAt): string
    {
        if (blank($expiredAt)) {
            return '—';
        }

        $expired = ($expiredAt instanceof \DateTimeInterface ? Carbon::instance($expiredAt) : Carbon::parse((string) $expiredAt))
            ->timezone(Waktu::zona())
            ->startOfDay();

        return (string) (int) Waktu::sekarang()->startOfDay()->diffInDays($expired, false);
    }
}
