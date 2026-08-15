<?php

namespace App\Filament\Resources\PresensiHariLiburs\Tables;

use App\Filament\Resources\PresensiHariLiburs\PresensiHariLiburResource;
use App\Services\TahunAjaranOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PresensiHariLibursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->description(fn ($record) => $record->tanggal?->translatedFormat('l'))
                    ->sortable(),

                TextColumn::make('keterangan')->label('Keterangan')->searchable(),

                TextColumn::make('tahun_ajaran')->label('Tahun Ajaran')->badge()->sortable(),
            ])
            ->defaultSort('tanggal', 'asc')
            ->filters([
                SelectFilter::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->options(TahunAjaranOptions::options()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record): bool => PresensiHariLiburResource::canDelete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Menghapus libur sepekan berarti memilih tujuh baris; bulk delete
                    // bukan kemewahan di sini, ia jalan keluar dari konsekuensi
                    // keputusan "satu baris per hari".
                    DeleteBulkAction::make()->visible(fn (): bool => PresensiHariLiburResource::canDeleteAny()),
                ]),
            ]);
    }
}
