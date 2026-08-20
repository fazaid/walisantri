<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

// #[Table] WAJIB — tanpa ini Laravel menebak tabelnya 'modul_pengaturans'.
#[Table('modul_pengaturan')]
#[Fillable([
    'pesantren_id',
    'akademik_aktif',
    'tahfidz_aktif',
    'presensi_aktif',
    'kesantrian_aktif',
    'keuangan_aktif',
    'rapor_aktif',
])]
class ModulPengaturan extends Model
{
    use BelongsToPesantren, Multitenantable;

    private const KUNCI_MEMO = 'modul_pengaturan.';

    protected function casts(): array
    {
        return [
            'akademik_aktif' => 'boolean',
            'tahfidz_aktif' => 'boolean',
            'presensi_aktif' => 'boolean',
            'kesantrian_aktif' => 'boolean',
            'keuangan_aktif' => 'boolean',
            'rapor_aktif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Memo dibuang begitu barisnya berubah. Tanpa ini, request yang menyimpan
        // pengaturan akan terus membaca nilai lama sampai request berikutnya —
        // dan halaman yang dirender setelah save akan memperlihatkan menu yang
        // baru saja dimatikan admin.
        static::saved(fn (self $pengaturan) => static::lupakan($pengaturan->pesantren_id));
    }

    /**
     * Pengaturan modul milik satu pesantren, dibuatkan bila belum ada.
     *
     * Ini lapis penyembuh KETIGA, dan ketiganya disengaja: ProvisionTenant mengisi
     * untuk tenant baru, migrasi 2026_08_21_000002 menambal tenant lama, dan method
     * ini menutup sisa kemungkinan apa pun. Modul Mutaba'ah pernah lumpuh diam-diam
     * berbulan-bulan justru karena satu-satunya pengisi datanya adalah migrasi yang
     * hanya jalan sekali (PRD §22, kelas bug v4.21).
     *
     * ⚠️ MEMOISASINYA DI CONTAINER, BUKAN static array — dan itu bukan selera.
     * Method ini dipanggil ±27 kali per render sidebar (sekali per komponen panel),
     * jadi tanpa memo ia jadi 27 query per halaman. Tapi `private static array $memo`
     * bertahan melewati RefreshDatabase antar-method di proses PHP yang sama,
     * sehingga tes kedua akan membaca toggle milik tes pertama — gagal dengan gejala
     * yang terbaca seperti bug produk, di berkas yang bukan berkas yang disunting.
     * Container dibangun ulang tiap request DAN tiap tes, jadi ia termemoisasi
     * persis selama itu masih aman.
     *
     * Sengaja TIDAK memakai Cache:: — pengaturan per-pesantren di repo ini tidak
     * pernah di-cache lintas request; toggle basi setelah admin membaliknya adalah
     * persis kelas kerusakan senyap yang komentar-komentar repo ini terus peringatkan.
     */
    public static function untuk(int $pesantrenId): self
    {
        $kunci = self::KUNCI_MEMO.$pesantrenId;

        if (! app()->bound($kunci)) {
            app()->instance($kunci, static::muat($pesantrenId));
        }

        return app($kunci);
    }

    public static function lupakan(int $pesantrenId): void
    {
        app()->forgetInstance(self::KUNCI_MEMO.$pesantrenId);
    }

    private static function muat(int $pesantrenId): self
    {
        // pesantren_id diisi EKSPLISIT, bukan mengandalkan auto-assign Multitenantable:
        // selain lebih jelas, itu juga membuat method ini tetap bekerja saat dipanggil
        // dari konteks tanpa sesi (ProvisionTenant dipanggil saat registrasi publik).
        $pengaturan = static::withoutGlobalScope('pesantren')
            ->firstOrCreate(['pesantren_id' => $pesantrenId]);

        // Nilai default kolom hidup di DB, bukan di model, jadi instance hasil
        // firstOrCreate() baru berisi pesantren_id saja — sisanya null sampai
        // dibaca ulang. Tanpa refresh ini, pemanggil pertama setelah baris dibuat
        // akan melihat SELURUH modul mati, kebalikan persis dari yang dimaksud.
        return $pengaturan->wasRecentlyCreated ? $pengaturan->refresh() : $pengaturan;
    }
}
