<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Sumber kebenaran tunggal nama grup sidebar panel admin.
 *
 * Dua hal yang wajib diingat saat menyunting enum ini:
 *
 * 1. URUTAN CASE = URUTAN GRUP DI SIDEBAR. Filament mengurutkan grup lewat
 *    array_search($case, $case::cases()) di NavigationManager::getNavigationGroups(),
 *    yang MENIMPA urutan pendaftaran di AdminPanelProvider::navigationGroups().
 *
 * 2. HasLabel wajib diimplementasikan. Tanpa itu Filament memakai $case->name
 *    (NavigationGroup::fromEnum()), sehingga PengaturanPlatform tampil sebagai
 *    "PengaturanPlatform", bukan "Pengaturan Platform".
 *
 * Item dikelompokkan berdasarkan serialize() nilai group, jadi string 'Manajemen'
 * dan self::Manajemen adalah dua grup BERBEDA. Jangan campur — semua resource/page
 * harus memakai enum ini.
 */
enum NavigationGroup: string implements HasLabel
{
    // Urusan platform lintas-tenant — hanya super admin.
    case Platform = 'Platform';

    // Transaksi langganan — hanya super admin.
    case Langganan = 'Langganan';

    // Halaman pengaturan platform — hanya super admin.
    case PengaturanPlatform = 'Pengaturan Platform';

    // Dipakai bersama super admin & admin pesantren.
    case Manajemen = 'Manajemen';

    public function getLabel(): string
    {
        return $this->value;
    }
}
