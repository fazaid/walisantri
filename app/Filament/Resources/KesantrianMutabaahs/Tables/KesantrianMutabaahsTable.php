<?php

// ============================================================
// FILE 3: app/Filament/Resources/KesantrianMutabaahs/Tables/KesantrianMutabaahsTable.php
// ============================================================

namespace App\Filament\Resources\KesantrianMutabaahs\Tables;

use App\Filament\Resources\KesantrianMutabaahs\KesantrianMutabaahResource;
use App\Filament\Support\SantriOptions;
use App\Models\KesantrianMutabaah;
use App\Services\MutabaahScoreCalculator;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KesantrianMutabaahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('santri.nama_lengkap')->label('Santri')->searchable()->sortable(),
                TextColumn::make('skor')
                    ->label('Skor Amalan')
                    ->state(fn (KesantrianMutabaah $record) => MutabaahScoreCalculator::persentase($record).'%')
                    ->badge()
                    ->color(function (KesantrianMutabaah $record): string {
                        $pct = MutabaahScoreCalculator::persentase($record);

                        return $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                    }),
                TextColumn::make('status_udzur')->label('Udzur')
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Tidak' => 'success',
                        'Sakit' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('status_udzur')->label('Status Udzur')
                    ->options([
                        'Tidak' => 'Tidak',
                        'Sakit' => 'Sakit',
                        'Haid' => 'Haid',
                        'Izin_Pulang' => 'Izin Pulang',
                        'Tugas_Pondok' => 'Tugas Pondok',
                    ]),
                SelectFilter::make('santri_id')->label('Santri')
                    ->options(fn () => SantriOptions::aktifUntukPengguna())
                    ->searchable(),
            ])
            // Filament v4 hanya membaca policy untuk mengunci action, dan aplikasi
            // ini tidak punya policy — jadi aturan "hapus khusus admin" dari
            // HasAdminUstadzAccess dipasang manual di sini.
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->modalWidth(Width::FourExtraLarge)
                    ->before(KesantrianMutabaahResource::guardTanggalBentrok()),
                DeleteAction::make()
                    ->visible(fn ($record): bool => KesantrianMutabaahResource::canDelete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => KesantrianMutabaahResource::canDeleteAny()),
                ]),
            ]);
    }
}
