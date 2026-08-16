<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Support\Waktu;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

/**
 * Dasbor bawaan Filament dibuka oleh AccountWidget — kartu "Selamat Datang"
 * berisi avatar, nama, dan tombol Keluar yang semuanya sudah tersedia di menu
 * pengguna topbar. Kartu itu menghabiskan satu baris penuh tanpa menambah
 * informasi, jadi widget-nya dilepas (lihat AdminPanelProvider) dan diganti
 * heading yang langsung menyebut peran pengguna, dengan jam WIB di bawahnya.
 *
 * getTitle() sengaja tidak dioverride: judul tab browser dan label sidebar
 * tetap "Dasbor", hanya heading di badan halaman yang menyesuaikan role.
 */
class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        $peran = match (Auth::user()?->role) {
            UserRole::SuperAdmin->value => ' Super Admin',
            UserRole::AdminPesantren->value => ' Admin',
            UserRole::Ustadz->value => ' Ustadz',
            default => '',
        };

        return 'Dasbor'.$peran;
    }

    public function getSubheading(): ?Htmlable
    {
        $sekarang = Waktu::sekarang();

        return new HtmlString(
            view('filament.admin.jam-sekarang', [
                'awal' => $sekarang->translatedFormat('l, d F Y').' · '.$sekarang->format('H:i:s').' WIB',
                'zona' => Waktu::zona(),
            ])->render(),
        );
    }
}
