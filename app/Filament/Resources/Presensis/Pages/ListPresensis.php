<?php

namespace App\Filament\Resources\Presensis\Pages;

use App\Enums\UserRole;
use App\Filament\Pages\PresensiHarianPage;
use App\Filament\Pages\PresensiPengaturanPage;
use App\Filament\Pages\PresensiScannerPage;
use App\Filament\Resources\Presensis\PresensiResource;
use App\Models\Kelas;
use App\Services\KartuPresensiPdf;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ListPresensis extends ListRecords
{
    protected static string $resource = PresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Kedua halaman sengaja tidak didaftarkan sebagai tab cluster (menu
            // Presensi cuma boleh berisi empat submenu); dua tombol ini jalan masuknya.
            Action::make('isiPresensi')
                ->label('Isi Presensi')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->url(PresensiHarianPage::getUrl())
                ->color('gray')
                ->visible(fn (): bool => PresensiHarianPage::canAccess()),

            Action::make('scanQr')
                ->label('Scan QR')
                ->icon(Heroicon::OutlinedQrCode)
                ->url(PresensiScannerPage::getUrl())
                ->color('gray')
                ->visible(fn (): bool => PresensiScannerPage::canAccess()),

            Action::make('cetakKartu')
                ->label('Cetak Kartu')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->visible(fn (): bool => Auth::user()?->role === UserRole::AdminPesantren->value)
                ->form([
                    Select::make('kelas_id')
                        ->label('Kelas')
                        ->options(fn () => Kelas::orderBy('nama_kelas')->pluck('nama_kelas', 'id'))
                        ->required()
                        ->helperText('Kartu dicetak untuk seluruh santri aktif di kelas ini.'),
                ])
                ->action(fn (array $data) => app(KartuPresensiPdf::class)
                    ->untukKelas(Kelas::findOrFail($data['kelas_id']))),

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
