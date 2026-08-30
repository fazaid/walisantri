<?php

namespace App\Filament\Resources\Santris\Pages;

use App\Exceptions\SantriQuotaExceededException;
use App\Exports\SantriTemplateExport;
use App\Filament\Resources\Santris\SantriResource;
use App\Imports\SantriImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Actions as FormActions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Log;
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
                            '<ul class="text-sm list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">'.
                            '<li><strong>Kolom wajib:</strong> <code>nis</code>, <code>nama_lengkap</code></li>'.
                            '<li><strong>Kolom opsional:</strong> nama_panggilan, tanggal_lahir <em>(DD/MM/YYYY)</em>, nama_ayah, nama_ibu, alamat_lengkap, jumlah_saudara, cita_cita, status</li>'.
                            '<li>Kolom <code>kelas</code> dan <code>kamar</code> harus sesuai nama yang sudah terdaftar di sistem.</li>'.
                            '<li>Kolom <code>status</code> diisi "Aktif" atau "Non-Aktif" — kosong dianggap Aktif.</li>'.
                            '<li>Baris dengan NIS yang sudah terdaftar akan <strong>dilewati</strong>, kecuali Anda menyalakan "Perbarui data santri yang sudah terdaftar" di bawah.</li>'.
                            '<li>Saat mode perbarui menyala, <strong>hanya kolom yang Anda isi</strong> yang menimpa data lama — sel yang dikosongkan dibiarkan apa adanya. Jadi file berisi <code>nis</code> + <code>kelas</code> saja cukup untuk kenaikan kelas massal.</li>'.
                            '<li>NIS milik santri yang sudah dihapus tetap dilewati. Pulihkan dulu santrinya bila memang ingin diperbarui.</li>'.
                            '<li><strong>Kolom opsional wali:</strong> wali_nama, wali_email, wali_no_hp — isi salah satu <code>wali_email</code> atau <code>wali_no_hp</code> untuk membuat/menautkan akun wali. Wali tanpa email tetap bisa pakai magic link portal lewat nomor WA. Kalau keduanya kosong, wali tidak ditautkan (santri tetap dibuat).</li>'.
                            '<li><strong>Sebaiknya isi keduanya bila Anda punya.</strong> Wali dicari lewat email <em>dan</em> nomor WA, jadi satu orang tua tetap satu akun walau di file lain hanya disebut lewat salah satunya. Kolom kontak yang masih kosong di akun wali akan dilengkapi dari file — yang <strong>sudah terisi tidak pernah ditimpa</strong>. Untuk mengoreksi email, nomor, atau nama wali, pakai menu Pengguna.</li>'.
                            '<li>Beberapa baris yang menunjuk wali yang sama akan ditautkan ke satu akun (untuk kakak-adik), termasuk bila baris yang satu menyebut emailnya dan baris lain menyebut nomor WA-nya.</li>'.
                            '</ul>'
                        )),
                    FormActions::make([
                        Action::make('unduh_template')
                            ->label('Unduh Template Excel')
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('gray')
                            ->action(fn () => Excel::download(new SantriTemplateExport, 'template-import-santri.xlsx')),
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

                    // Default mati, dan itu disengaja: admin yang mengunggah ulang
                    // file lama tanpa sadar tidak boleh memundurkan data yang sudah
                    // disunting manual di panel. Live supaya angka di pratinjau
                    // ikut berubah begitu togglenya disentuh — kalau tidak, admin
                    // menekan tombol berdasarkan ringkasan yang bukan miliknya.
                    Toggle::make('perbarui_yang_ada')
                        ->label('Perbarui data santri yang sudah terdaftar')
                        ->helperText('Kalau mati, baris ber-NIS yang sudah ada akan dilewati. Kalau menyala, hanya kolom yang terisi di file yang menimpa data lama — sel kosong tidak menghapus apa pun.')
                        ->default(false)
                        ->live(),

                    Placeholder::make('preview')
                        ->label('Ringkasan Sebelum Import')
                        ->content(function (Get $get) {
                            $path = $get('file');

                            if (! $path) {
                                return null;
                            }

                            try {
                                $pesantrenId = auth()->user()->pesantren_id;
                                $perbarui = (bool) $get('perbarui_yang_ada');
                                $rows = Excel::toCollection(new SantriImport($pesantrenId), $path, 'local')->first() ?? collect();
                                $ringkasan = (new SantriImport($pesantrenId, $perbarui))->analyze($rows);
                            } catch (\Throwable $e) {
                                Log::warning('Preview import santri gagal membaca file.', [
                                    'pesantren_id' => auth()->user()->pesantren_id ?? null,
                                    'path' => $path,
                                    'exception' => $e::class,
                                    'message' => $e->getMessage(),
                                ]);

                                return new HtmlString(
                                    '<p class="text-sm text-danger-600 dark:text-danger-400">File tidak bisa dibaca untuk pratinjau. Pastikan format .xlsx sesuai template.</p>'
                                );
                            }

                            $items = ['Total baris terbaca: <strong>'.$ringkasan['total'].'</strong>'];
                            $items[] = '<span class="text-success-600 dark:text-success-400">Akan diimpor: <strong>'.$ringkasan['akan_diimpor'].'</strong></span>';

                            if ($ringkasan['akan_diperbarui'] > 0) {
                                $items[] = '<span class="text-info-600 dark:text-info-400">Akan diperbarui: <strong>'.$ringkasan['akan_diperbarui'].'</strong></span>';
                            }
                            if ($ringkasan['duplikat'] > 0) {
                                $items[] = '<span class="text-warning-600 dark:text-warning-400">NIS duplikat, akan dilewati: <strong>'.$ringkasan['duplikat'].'</strong></span>';
                            }
                            if ($ringkasan['dihapus'] > 0) {
                                $items[] = '<span class="text-warning-600 dark:text-warning-400">NIS milik santri terhapus, akan dilewati: <strong>'.$ringkasan['dihapus'].'</strong></span>';
                            }
                            if ($ringkasan['data_wajib_kosong'] > 0) {
                                $items[] = '<span class="text-warning-600 dark:text-warning-400">NIS/Nama Lengkap kosong, akan dilewati: <strong>'.$ringkasan['data_wajib_kosong'].'</strong></span>';
                            }
                            if ($ringkasan['melebihi_kuota'] > 0) {
                                $items[] = '<span class="text-danger-600 dark:text-danger-400">Melebihi sisa kuota paket, akan dilewati: <strong>'.$ringkasan['melebihi_kuota'].'</strong></span>';
                            }
                            if ($ringkasan['wali_baru'] > 0) {
                                $items[] = '<span class="text-info-600 dark:text-info-400">Akun wali baru akan dibuat: <strong>'.$ringkasan['wali_baru'].'</strong></span>';
                            }

                            return new HtmlString(
                                '<ul class="text-sm list-disc list-inside space-y-1">'.
                                collect($items)->map(fn ($item) => "<li>{$item}</li>")->implode('').
                                '</ul>'
                            );
                        })
                        ->visible(fn (Get $get) => filled($get('file'))),
                ])
                ->action(function (array $data): void {
                    $pesantrenId = auth()->user()->pesantren_id;
                    $import = new SantriImport($pesantrenId, (bool) ($data['perbarui_yang_ada'] ?? false));

                    try {
                        Excel::import($import, $data['file'], 'local');
                    } catch (\Throwable $e) {
                        Log::warning('Import santri gagal memproses file.', [
                            'pesantren_id' => $pesantrenId,
                            'path' => $data['file'] ?? null,
                            'exception' => $e::class,
                            'message' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('File Tidak Valid')
                            ->body('Gagal memproses file. Pastikan format .xlsx dan menggunakan template yang benar.')
                            ->danger()
                            ->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($data['file']);
                    }

                    $bagian = [];
                    if ($import->imported > 0 || $import->updated === 0) {
                        $bagian[] = "Berhasil mengimpor {$import->imported} santri.";
                    }
                    if ($import->updated > 0) {
                        $bagian[] = "{$import->updated} santri diperbarui.";
                    }
                    if ($import->skipped > 0) {
                        $bagian[] = "{$import->skipped} baris dilewati.";
                    }
                    $body = implode(' ', $bagian);

                    // Pembaruan ikut dihitung sebagai keberhasilan. Tanpa ini, impor
                    // yang isinya murni pembaruan — kasus paling wajar dari mode ini —
                    // dilaporkan sebagai "Import Gagal" padahal semua barisnya masuk.
                    if ($import->imported > 0 || $import->updated > 0) {
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
                            $detail .= "\n... dan ".(count($import->errors) - 10).' pesan lainnya.';
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

            CreateAction::make()
                ->visible(fn () => static::getResource()::canCreate())
                ->modalWidth(Width::FourExtraLarge)
                ->using(function (array $data, string $model, Action $action) {
                    try {
                        return $model::create($data);
                    } catch (SantriQuotaExceededException $e) {
                        Notification::make()
                            ->title('Kuota Santri Penuh')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
