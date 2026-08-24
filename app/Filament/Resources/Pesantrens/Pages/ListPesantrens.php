<?php

namespace App\Filament\Resources\Pesantrens\Pages;

use App\Enums\UserRole;
use App\Exports\DataPesantrenExport;
use App\Filament\Resources\Pesantrens\PesantrenResource;
use App\Models\Pesantren;
use App\Services\ProvisionTenant;
use App\Support\Waktu;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListPesantrens extends ListRecords
{
    protected static string $resource = PesantrenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                // Tooltipnya bukan hiasan: TernaryFilter `is_demo` menyembunyikan
                // tenant sandbox secara bawaan, jadi berkas standar berisi
                // pelanggan saja. Tanpa penjelasan itu terbaca sebagai data hilang.
                ->tooltip('Mengunduh baris yang sedang tampil — filter, pencarian, dan urutan tabel ikut terbawa.')
                ->action(function (): BinaryFileResponse {
                    // Lapis kedua di atas canAccess() milik resource, mengikuti
                    // kebiasaan Admin\ExportController.
                    abort_unless(auth()->user()?->role === UserRole::SuperAdmin->value, 403);

                    // Method yang sama yang dipakai ExportAction bawaan Filament:
                    // modifyQueryUsing + filter + pencarian + sortir, sudah di-clone.
                    return Excel::download(
                        new DataPesantrenExport($this->getTableQueryForExport()),
                        'data-pesantren-'.Waktu::sekarang()->format('Y-m-d').'.xlsx',
                    );
                }),

            CreateAction::make()
                ->modalWidth(Width::TwoExtraLarge)
                // Tanpa ini pesantren yang dibuat dari panel lahir setengah jadi:
                // tidak punya baris tenant_domains (subdomainnya 404 selamanya) dan
                // tidak punya amalan bawaan (modul Mutaba'ah lumpuh diam-diam).
                // Langkah yang sama dipakai jalur pendaftaran publik lewat
                // OnboardPesantren, jadi kedua jalur menghasilkan tenant yang setara.
                ->after(fn (Pesantren $record) => app(ProvisionTenant::class)->jalankan($record)),
        ];
    }
}
