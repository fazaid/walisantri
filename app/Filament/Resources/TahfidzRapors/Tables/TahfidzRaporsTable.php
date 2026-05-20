<?php

// ============================================================
// FILE 3: app/Filament/Resources/TahfidzRapors/Tables/TahfidzRaporsTable.php
// ============================================================

namespace App\Filament\Resources\TahfidzRapors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TahfidzRaporsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('santri.nama_lengkap')->label('Santri')->searchable()->sortable(),
                TextColumn::make('tahun_ajaran')->label('Tahun Ajaran')->sortable(),
                TextColumn::make('periode')->label('Periode')
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state)),
                TextColumn::make('nilai_hafalan')->label('Nilai Hafalan'),
                TextColumn::make('nilai_tilawah')->label('Tilawah')->badge(),
                TextColumn::make('nilai_makhraj')->label('Makhraj')->badge(),
                TextColumn::make('nilai_tajwid')->label('Tajwid')->badge(),
            ])
            ->defaultSort('tahun_ajaran', 'desc')
            ->filters([
                SelectFilter::make('periode')->label('Periode')
                    ->options([
                        'Bulanan'        => 'Bulanan',
                        'Semester_Ganjil'=> 'Semester Ganjil',
                        'Semester_Genap' => 'Semester Genap',
                    ]),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}