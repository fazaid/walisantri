<?php

namespace App\Filament\Resources\TahfidzUjian\Tables;

use App\Services\TahunAjaranOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TahfidzUjianTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_ujian')->label('Tanggal Ujian')->date('d M Y')->sortable(),
                TextColumn::make('santri.nama_lengkap')->label('Santri')->searchable()->sortable(),
                TextColumn::make('penguji.name')->label('Penguji')->searchable()->sortable(),
                TextColumn::make('target_juz')->label('Target Juz')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} Juz" : '—')->sortable(),
                TextColumn::make('status_kelulusan')->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Lulus' => 'success',
                        'Mengulang' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('tahun_ajaran')->label('Tahun Ajaran')->sortable(),
                TextColumn::make('periode')->label('Periode')
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state)),
                TextColumn::make('bulan')->label('Bulan')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nilai_hafalan')->label('Nilai Hafalan'),
                TextColumn::make('nilai_tilawah')->label('Tilawah')->badge(),
                TextColumn::make('nilai_makhraj')->label('Makhraj')->badge(),
                TextColumn::make('nilai_tajwid')->label('Tajwid')->badge(),
            ])
            ->defaultSort('tanggal_ujian', 'desc')
            ->filters([
                SelectFilter::make('periode')->label('Periode')
                    ->options(TahunAjaranOptions::periodeOptions()),
                SelectFilter::make('status_kelulusan')->label('Status')
                    ->options(['Lulus' => 'Lulus', 'Mengulang' => 'Mengulang']),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
