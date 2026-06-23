<?php

// ============================================================
// FILE 3: app/Filament/Resources/KesantrianMutabaahs/Tables/KesantrianMutabaahsTable.php
// ============================================================

namespace App\Filament\Resources\KesantrianMutabaahs\Tables;

use App\Models\KesantrianMutabaah;
use App\Services\MutabaahScoreCalculator;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                        'Tidak'        => 'success',
                        'Sakit'        => 'danger',
                        default        => 'warning',
                    }),
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
