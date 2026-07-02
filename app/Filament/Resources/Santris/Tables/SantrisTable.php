<?php

// File: app/Filament/Resources/Santris/Tables/SantrisTable.php

namespace App\Filament\Resources\Santris\Tables;

use App\Enums\JenisKelamin;
use App\Filament\Resources\Santris\Actions\KirimMagicLinkAction;
use App\Filament\Resources\Santris\Actions\PindahKamarBulkAction;
use App\Filament\Resources\Santris\Actions\PindahKelasBulkAction;
use App\Filament\Resources\Santris\Actions\RegenerasiUuidAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
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
                    ->toggleable(),
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
                TextColumn::make('pembimbing.name')
                    ->label('Ustadz Pembimbing')
                    ->searchable()
                    ->toggleable(),
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
                KirimMagicLinkAction::make(),
                RegenerasiUuidAction::make(),
                EditAction::make(),
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