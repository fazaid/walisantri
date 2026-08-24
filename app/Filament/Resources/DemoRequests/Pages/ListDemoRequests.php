<?php

namespace App\Filament\Resources\DemoRequests\Pages;

use App\Enums\UserRole;
use App\Exports\AntreanDemoExport;
use App\Filament\Resources\DemoRequests\DemoRequestResource;
use App\Support\Waktu;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListDemoRequests extends ListRecords
{
    protected static string $resource = DemoRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->tooltip('Mengunduh baris yang sedang tampil — filter, pencarian, dan urutan tabel ikut terbawa.')
                ->action(function (): BinaryFileResponse {
                    // Resource sudah digating canAccess(), ini lapis kedua —
                    // kebiasaan yang sama dengan Admin\ExportController.
                    abort_unless(auth()->user()?->role === UserRole::SuperAdmin->value, 403);

                    // getTableQueryForExport() adalah method yang dipakai
                    // ExportAction bawaan Filament sendiri: ia menggabungkan
                    // modifyQueryUsing + filter + pencarian + sortir, dan sudah
                    // mengembalikan clone. Rute terpisah (pola admin.export.*)
                    // tidak bisa melihat state filter Livewire sama sekali.
                    return Excel::download(
                        new AntreanDemoExport($this->getTableQueryForExport()),
                        'antrean-demo-'.Waktu::sekarang()->format('Y-m-d').'.xlsx',
                    );
                }),
        ];
    }
}
