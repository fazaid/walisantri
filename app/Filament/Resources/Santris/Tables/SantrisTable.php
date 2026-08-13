<?php

// File: app/Filament/Resources/Santris/Tables/SantrisTable.php

namespace App\Filament\Resources\Santris\Tables;

use App\Enums\JenisKelamin;
use App\Filament\Resources\Santris\Actions\PindahKamarBulkAction;
use App\Filament\Resources\Santris\Actions\PindahKelasBulkAction;
use App\Models\Santri;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SantrisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama_lengkap')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kamar.nama_kamar')
                    ->label('Kamar')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('wali.name')
                    ->label('Wali Santri')
                    ->searchable()
                    ->toggleable(),
                // Kolom hitungan: yang tampil cuma badge pendek, yang tersalin
                // URL penuh lewat copyableState(). Tanpa copyableState eksplisit
                // Filament menyalin teks badge-nya, bukan link-nya.
                TextColumn::make('link_wali')
                    ->label('Link Wali')
                    ->badge()
                    ->state(fn (Santri $record): string => $record->wali_santri_id === null
                        ? '— belum ada wali —'
                        : 'Salin Link')
                    ->color(fn (Santri $record): string => $record->wali_santri_id === null ? 'gray' : 'info')
                    ->icon(fn (Santri $record): ?string => $record->wali_santri_id === null ? null : 'heroicon-o-link')
                    ->copyable(fn (Santri $record): bool => $record->wali_santri_id !== null)
                    ->copyableState(fn (Santri $record): string => $record->linkWali())
                    ->copyMessage('Link portal wali tersalin')
                    ->tooltip(fn (Santri $record): string => $record->wali_santri_id === null
                        ? 'Hubungkan santri ke akun wali dulu lewat Edit → bagian Relasi.'
                        : $record->linkWali())
                    ->toggleable(),
                TextColumn::make('pembimbing.name')
                    ->label('Ustadz Pembimbing')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('status_aktif')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nama_lengkap', 'asc')
            ->filters([
                SelectFilter::make('jenis_kelamin')
                    ->label('Filter Jenis Kelamin')
                    ->options(JenisKelamin::options()),
                SelectFilter::make('kelas_id')
                    ->label('Filter Kelas')
                    ->relationship('kelas', 'nama_kelas'),
                SelectFilter::make('kamar_id')
                    ->label('Filter Kamar')
                    ->relationship('kamar', 'nama_kamar'),
                TernaryFilter::make('status_aktif')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Non-Aktif'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->modalWidth(Width::FourExtraLarge),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    PindahKelasBulkAction::make(),
                    PindahKamarBulkAction::make(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
