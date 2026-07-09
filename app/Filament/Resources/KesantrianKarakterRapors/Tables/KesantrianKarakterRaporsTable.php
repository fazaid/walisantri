<?php

// ============================================================
// FILE 3: app/Filament/Resources/KesantrianKarakterRapors/Tables/KesantrianKarakterRaporsTable.php
// ============================================================

namespace App\Filament\Resources\KesantrianKarakterRapors\Tables;

use App\Filament\Support\SantriOptions;
use App\Services\TahunAjaranOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KesantrianKarakterRaporsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_input')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('santri.nama_lengkap')->label('Santri')->searchable()->sortable(),
                TextColumn::make('tahun_ajaran')->label('Tahun Ajaran')->sortable(),
                TextColumn::make('periode')
                    ->label('Periode')
                    ->formatStateUsing(fn (string $state) => str_replace('_', ' ', $state)),
                TextColumn::make('adab_ustadz')->label('Adab Ustadz')->badge(),
                TextColumn::make('kepribadian_kedisiplinan')->label('Kedisiplinan')->badge(),
            ])
            ->defaultSort('tanggal_input', 'desc')
            ->filters([
                SelectFilter::make('periode')->label('Periode')
                    ->options(TahunAjaranOptions::periodeOptions()),
                SelectFilter::make('santri_id')->label('Santri')
                    ->options(fn () => SantriOptions::aktifUntukPengguna())
                    ->searchable(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
