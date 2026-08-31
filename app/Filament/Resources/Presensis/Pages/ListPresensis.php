<?php

namespace App\Filament\Resources\Presensis\Pages;

use App\Filament\Pages\PresensiHarianPage;
use App\Filament\Pages\PresensiJamPage;
use App\Filament\Pages\PresensiPengaturanPage;
use App\Filament\Pages\PresensiScannerPage;
use App\Filament\Resources\Presensis\PresensiResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListPresensis extends ListRecords
{
    protected static string $resource = PresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Halaman-halaman berikut sengaja tidak didaftarkan sebagai tab cluster
            // (menu Presensi cuma boleh berisi empat submenu); tombol-tombol ini
            // jalan masuknya.
            Action::make('isiPresensi')
                ->label('Isi Presensi')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->url(PresensiHarianPage::getUrl())
                ->color('gray')
                ->visible(fn (): bool => PresensiHarianPage::canAccess()),

            // Hanya muncul saat mode per jam pelajaran dinyalakan admin. Bawaannya
            // mati, jadi pesantren yang tidak memakainya tidak melihat perubahan
            // apa pun di header ini.
            Action::make('isiPresensiJam')
                ->label('Isi per Jam')
                ->icon(Heroicon::OutlinedClock)
                ->url(PresensiJamPage::getUrl())
                ->color('gray')
                ->visible(fn (): bool => PresensiJamPage::aktifUntukPengguna()),

            Action::make('scanQr')
                ->label('Scan QR')
                ->icon(Heroicon::OutlinedQrCode)
                ->url(PresensiScannerPage::getUrl())
                ->color('gray')
                ->visible(fn (): bool => PresensiScannerPage::canAccess()),

            // Tombol "Cetak Kartu" dulu berdiri di sini. Dipindah ke Santri →
            // Data Santri: yang dicetak adalah kartu identitas milik santri, dan
            // presensi hanya salah satu pemakainya. Jangan dikembalikan ke sini —
            // kartunya sekarang ada dua jenis, dan yang kedua tidak ada urusannya
            // dengan kehadiran sama sekali.

            Action::make('pengaturanPresensi')
                ->label('Pengaturan')
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->url(PresensiPengaturanPage::getUrl())
                ->color('gray')
                ->visible(fn (): bool => PresensiPengaturanPage::canAccess()),

            CreateAction::make()->modalWidth(Width::TwoExtraLarge),
        ];
    }
}
