<?php

namespace App\Support;

use App\Models\Wilayah;
use Illuminate\Support\Str;

/**
 * Menurunkan jalur wilayah lengkap (provinsi → desa) dari SATU kode desa/kelurahan.
 *
 * Kode desa '32.01.01.1006' sudah memuat seluruh leluhurnya, jadi induknya tidak
 * perlu — dan tidak boleh — dipercaya dari klien. Payload yang disunting mustahil
 * menghasilkan baris `profil['wilayah']` yang tidak konsisten.
 */
final class WilayahLookup
{
    /** @var array<string, array<string, array{kode: string, nama: string}>|null> memo per-request */
    private array $memo = [];

    /**
     * Satu query untuk empat level, lalu dimemo: pemanggil kedua dalam request yang
     * sama — rule validasi lalu perakit profil di controller — tidak menyentuh
     * database sama sekali.
     *
     * @return array<string, array{kode: string, nama: string}>|null null bila kodenya tidak dikenali
     */
    public function jalurDariDesa(string $kodeDesa): ?array
    {
        if (array_key_exists($kodeDesa, $this->memo)) {
            return $this->memo[$kodeDesa];
        }

        $kecamatan = Str::beforeLast($kodeDesa, '.');
        $kota = Str::beforeLast($kecamatan, '.');
        $provinsi = Str::beforeLast($kota, '.');

        // beforeLast() mengembalikan string utuh saat tidak ada titik — itulah deteksi
        // "kode terlalu dangkal" tanpa perlu memeriksa panjangnya lagi.
        if ($provinsi === $kota || $kota === $kecamatan || $kecamatan === $kodeDesa) {
            return $this->memo[$kodeDesa] = null;
        }

        $nama = Wilayah::query()
            ->whereIn('kode', [$provinsi, $kota, $kecamatan, $kodeDesa])
            ->pluck('nama', 'kode');

        if ($nama->count() !== 4) {
            return $this->memo[$kodeDesa] = null;
        }

        return $this->memo[$kodeDesa] = [
            'provinsi' => ['kode' => $provinsi, 'nama' => $nama[$provinsi]],
            'kota' => ['kode' => $kota, 'nama' => $nama[$kota]],
            'kecamatan' => ['kode' => $kecamatan, 'nama' => $nama[$kecamatan]],
            'desa' => ['kode' => $kodeDesa, 'nama' => $nama[$kodeDesa]],
        ];
    }
}
