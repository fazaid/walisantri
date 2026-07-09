<?php

namespace App\Filament\Resources\Santris\Pages;

use App\Exports\SantriTemplateExport;
use App\Filament\Resources\Santris\SantriResource;
use App\Imports\SantriImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Actions as FormActions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;

class ListSantris extends ListRecords
{
    protected static string $resource = SantriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_santri')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn () => auth()->user()?->role === 'admin_pesantren')
                ->modalHeading('Import Data Santri')
                ->form([
                    Placeholder::make('panduan')
                        ->label('Panduan')
                        ->content(new HtmlString(
                            '<ul class="text-sm list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">' .
                            '<li><strong>Kolom wajib:</strong> <code>nis</code>, <code>nama_lengkap</code></li>' .
                            '<li><strong>Kolom opsional:</strong> nama_panggilan, tanggal_lahir <em>(DD/MM/YYYY)</em>, nama_ayah, nama_ibu, alamat_lengkap, jumlah_saudara, cita_cita, status</li>' .
                            '<li>Kolom <code>kelas</code> dan <code>kamar</code> harus sesuai nama yang sudah terdaftar di sistem.</li>' .
                            '<li>Kolom <code>status</code> diisi "Aktif" atau "Non-Aktif" — kosong dianggap Aktif.</li>' .
                            '<li>Baris dengan NIS yang sudah terdaftar akan dilewati.</li>' .
                            '</ul>'
                        )),
                    FormActions::make([
                        Action::make('unduh_template')
                            ->label('Unduh Template Excel')
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('gray')
                            ->action(fn () => Excel::download(new SantriTemplateExport(), 'template-import-santri.xlsx')),
                    ])->fullWidth(),
                    FileUpload::make('file')
                        ->label('File Excel (.xlsx)')
                        ->disk('local')
                        ->directory('santri-imports')
                        ->visibility('private')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required()
                        ->maxSize(5120)
                        ->live()
                        ->helperText('Maks. 5 MB.'),
                    Placeholder::make('preview')
                        ->label('Ringkasan Sebelum Import')
                        ->content(function (Get $get) {
                            $path = $get('file');

                            if (! $path) {
                                return null;
                            }

                            try {
                                $pesantrenId = auth()->user()->pesantren_id;
                                $rows        = Excel::toCollection(new SantriImport($pesantrenId), $path, 'local')->first() ?? collect();
                                $ringkasan   = (new SantriImport($pesantrenId))->analyze($rows);
                            } catch (\Throwable) {
                                return new HtmlString(
                                    '<p class="text-sm text-danger-600 dark:text-danger-400">File tidak bisa dibaca untuk pratinjau. Pastikan format .xlsx sesuai template.</p>'
                                );
                            }

                            $items = ['Total baris terbaca: <strong>' . $ringkasan['total'] . '</strong>'];
                            $items[] = '<span class="text-success-600 dark:text-success-400">Akan diimpor: <strong>' . $ringkasan['akan_diimpor'] . '</strong></span>';

                            if ($ringkasan['duplikat'] > 0) {
                                $items[] = '<span class="text-warning-600 dark:text-warning-400">NIS duplikat, akan dilewati: <strong>' . $ringkasan['duplikat'] . '</strong></span>';
                            }
                            if ($ringkasan['data_wajib_kosong'] > 0) {
                                $items[] = '<span class="text-warning-600 dark:text-warning-400">NIS/Nama Lengkap kosong, akan dilewati: <strong>' . $ringkasan['data_wajib_kosong'] . '</strong></span>';
                            }
                            if ($ringkasan['melebihi_kuota'] > 0) {
                                $items[] = '<span class="text-danger-600 dark:text-danger-400">Melebihi sisa kuota paket, akan dilewati: <strong>' . $ringkasan['melebihi_kuota'] . '</strong></span>';
                            }

                            return new HtmlString(
                                '<ul class="text-sm list-disc list-inside space-y-1">' .
                                collect($items)->map(fn ($item) => "<li>{$item}</li>")->implode('') .
                                '</ul>'
                            );
                        })
                        ->visible(fn (Get $get) => filled($get('file'))),
                ])
                ->action(function (array $data): void {
                    $pesantrenId = auth()->user()->pesantren_id;
                    $import      = new SantriImport($pesantrenId);

                    try {
                        Excel::import($import, $data['file'], 'local');
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('File Tidak Valid')
                            ->body('Gagal memproses file. Pastikan format .xlsx dan menggunakan template yang benar.')
                            ->danger()
                            ->send();
                        return;
                    } finally {
                        Storage::disk('local')->delete($data['file']);
                    }

                    $body = "Berhasil mengimpor {$import->imported} santri.";
                    if ($import->skipped > 0) {
                        $body .= " {$import->skipped} baris dilewati.";
                    }

                    if ($import->imported > 0) {
                        Notification::make()
                            ->title('Import Selesai')
                            ->body($body)
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Import Gagal')
                            ->body('Tidak ada data yang berhasil diimpor. Periksa kembali file Anda.')
                            ->danger()
                            ->send();
                    }

                    if ($import->errors) {
                        $detail = implode("\n", array_slice($import->errors, 0, 10));
                        if (count($import->errors) > 10) {
                            $detail .= "\n... dan " . (count($import->errors) - 10) . ' pesan lainnya.';
                        }

                        Notification::make()
                            ->title('Detail Peringatan Import')
                            ->body($detail)
                            ->warning()
                            ->persistent()
                            ->send();
                    }
                }),

            Action::make('export_excel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => auth()->user()?->role === 'admin_pesantren')
                ->url(fn () => route('admin.export.santri')),

            CreateAction::make()->visible(fn () => static::getResource()::canCreate()),
        ];
    }
}
