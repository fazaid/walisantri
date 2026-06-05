<?php

namespace App\Filament\Resources\PrestasiSantris\Tables;

use App\Enums\TingkatPrestasi;
use App\Models\PrestasiSantri;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PrestasiSantrisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('santri.nama_lengkap')
                    ->label('Santri')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('judul')
                    ->label('Judul Prestasi')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color('info'),

                TextColumn::make('posisi')
                    ->label('Posisi')
                    ->placeholder('—'),

                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->badge()
                    ->color(fn (PrestasiSantri $record): string => $record->tingkat->color())
                    ->formatStateUsing(fn (PrestasiSantri $record): string => $record->tingkat->label()),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tingkat')
                    ->label('Tingkat')
                    ->options(TingkatPrestasi::options()),

                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options(PrestasiSantri::$kategoriOptions),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
