<?php

namespace App\Filament\Support;

use App\Enums\Modul;
use App\Filament\Clusters;
use App\Filament\Pages\RaporPage;

/**
 * Modul mana yang memiliki sebuah komponen panel — Resource atau Page.
 *
 * Pemetaannya DITURUNKAN DARI CLUSTER, bukan dari properti yang ditulis ulang di
 * tiap kelas. Dua konsekuensi yang keduanya disengaja:
 *
 * 1. `HasAdminUstadzAccess` dan `HasAdminOnlyAccess` bisa memanggil helper ini
 *    sekali untuk 13 resource sekaligus, dan `KelasResource`/`KamarResource` yang
 *    ikut memakai trait itu tetap aman — keduanya ada di Cluster Santri, yang tidak
 *    punya modul, jadi jawabannya null dan gate-nya tidak berubah sama sekali.
 * 2. Resource yang ditambahkan ke sebuah cluster tahun depan mewarisi gate modulnya
 *    sejak hari ia ditulis, tanpa ada yang perlu ingat menambahkannya di sini.
 *
 * Cluster Santri (Santri · Kelas · Kamar · Prestasi) sengaja tidak punya modul: ia
 * inti sistem, dan tuas untuk mematikannya tidak boleh pernah ada.
 */
class ModulKomponen
{
    /**
     * @param  class-string  $kelas
     */
    public static function modul(string $kelas): ?Modul
    {
        // Satu-satunya komponen top-level (tanpa cluster) yang bisa dimatikan.
        if (is_a($kelas, RaporPage::class, true)) {
            return Modul::Rapor;
        }

        return match ($kelas::getCluster()) {
            Clusters\Akademik::class => Modul::Akademik,
            Clusters\Tahfidz::class => Modul::Tahfidz,
            Clusters\Presensi::class => Modul::Presensi,
            Clusters\Kesantrian::class => Modul::Kesantrian,
            Clusters\Keuangan::class => Modul::Keuangan,
            // Cluster Santri, dan komponen apa pun di luar cluster (Dashboard,
            // Pengguna, Pengumuman, Billing, Pengaturan, Pengaturan Modul sendiri).
            default => null,
        };
    }

    /**
     * @param  class-string  $kelas
     */
    public static function aktif(string $kelas): bool
    {
        return static::modul($kelas)?->aktif() ?? true;
    }
}
