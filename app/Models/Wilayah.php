<?php

// File: app/Models/Wilayah.php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Referensi wilayah Kemendagri (§3.1): provinsi → kab/kota → kecamatan → desa/kelurahan,
 * dihubungkan `parent_kode`. Read-only bagi aplikasi — satu-satunya penulisnya
 * `php artisan wilayah:impor`.
 */
#[Table('wilayah')]
class Wilayah extends Model
{
    protected $primaryKey = 'kode';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * Anak langsung dari sebuah kode; `null` berarti akar, yaitu daftar provinsi.
     *
     * Sengaja mengembalikan Collection berisi array polos, BUKAN model. Hasilnya
     * dipakai tiga tempat (endpoint JSON, <option> di Blade, opsi Select Filament)
     * yang semuanya hanya butuh kode+nama — dan daftar provinsi ikut di-cache,
     * sedangkan model Eloquent yang di-serialize ke tabel cache bangkit kembali
     * sebagai __PHP_Incomplete_Class begitu autoloader proses pembaca berbeda.
     *
     * @return Collection<int, array{kode: string, nama: string}>
     */
    public static function anak(?string $parentKode): Collection
    {
        if (blank($parentKode)) {
            return static::provinsi();
        }

        return static::ambil(fn ($query) => $query->where('parent_kode', $parentKode));
    }

    /**
     * Daftar provinsi — 38 baris yang dirender server-side di setiap GET /register,
     * jadi satu-satunya level yang layak di-cache.
     *
     * Level lain sengaja TIDAK di-cache: CACHE_STORE default 'database', sehingga
     * cache lookup = satu SELECT + unserialize — tidak lebih murah dari
     * `where parent_kode = ?` pada index btree yang mengembalikan ≤ 100 baris, dan
     * akan menaburkan ribuan baris di tabel cache. Kalau kelak pindah ke Redis,
     * memperluas cache ke semua level baru menguntungkan.
     *
     * @return Collection<int, array{kode: string, nama: string}>
     */
    public static function provinsi(): Collection
    {
        return collect(Cache::remember(
            'wilayah:provinsi',
            86400,
            fn () => static::ambil(fn ($query) => $query->whereNull('parent_kode'))->all()
        ));
    }

    /**
     * @param  callable(Builder): mixed  $saringan
     * @return Collection<int, array{kode: string, nama: string}>
     */
    private static function ambil(callable $saringan): Collection
    {
        return static::query()
            ->tap($saringan)
            ->orderBy('nama')
            ->pluck('nama', 'kode')
            ->map(fn (string $nama, string $kode) => ['kode' => $kode, 'nama' => $nama])
            ->values();
    }
}
