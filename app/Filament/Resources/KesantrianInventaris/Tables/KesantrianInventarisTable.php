<?php

// ============================================================
// FILE 3: app/Filament/Resources/KesantrianInventaris/Tables/KesantrianInventarisTable.php
// ============================================================

namespace App\Filament\Resources\KesantrianInventaris\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KesantrianInventarisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('santri.nama_lengkap')->label('Santri')->searchable()->sortable(),
                TextColumn::make('nama_barang_umum')->label('Barang')->searchable(),
                TextColumn::make('kode_unik_fisik')->label('Kode')->searchable(),
                TextColumn::make('kondisi_barang')->label('Kondisi')
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Baik'        => 'success',
                        'Layak_Rusak' => 'warning',
                        'Hilang'      => 'danger',
                    }),
                TextColumn::make('tanggal_sidak_terakhir')->label('Sidak Terakhir')
                    ->date('d M Y')->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('kondisi_barang')->label('Kondisi')
                    ->options(['Baik'=>'Baik','Layak_Rusak'=>'Layak Rusak','Hilang'=>'Hilang']),
                SelectFilter::make('santri_id')->label('Santri')
                    ->options(function () {
                        $query = \App\Models\Santri::where('status_aktif', true);
                        if (auth()->user()?->role === 'ustadz') {
                            $query->where('pembimbing_ustadz_id', auth()->id());
                        }
                        return $query->orderBy('nama_lengkap')->pluck('nama_lengkap', 'id');
                    })
                    ->searchable(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}