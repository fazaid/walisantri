<?php

// ============================================================
// FILE 3: app/Filament/Resources/KesantrianMutabaahs/Tables/KesantrianMutabaahsTable.php
// ============================================================

namespace App\Filament\Resources\KesantrianMutabaahs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
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
                TextColumn::make('jamaah_5_waktu')->label('Jamaah'),
                TextColumn::make('status_udzur')->label('Udzur')
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Tidak'        => 'success',
                        'Sakit'        => 'danger',
                        default        => 'warning',
                    }),
                IconColumn::make('is_rawatib')->label('Rawatib')->boolean(),
                IconColumn::make('is_dhuha')->label('Dhuha')->boolean(),
                IconColumn::make('is_tilawah_1juz')->label('Tilawah')->boolean(),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('status_udzur')->label('Status Udzur')
                    ->options([
                        'Tidak'        => 'Tidak',
                        'Sakit'        => 'Sakit',
                        'Haid'         => 'Haid',
                        'Izin_Pulang'  => 'Izin Pulang',
                        'Tugas_Pondok' => 'Tugas Pondok',
                    ]),
                SelectFilter::make('santri')->label('Santri')
                    ->relationship('santri', 'nama_lengkap')->searchable(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}