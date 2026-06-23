<?php

namespace App\Filament\Resources\KesantrianAmalMasters\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KesantrianAmalMasterTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('urutan')->label('Urutan')->sortable(),
                TextColumn::make('icon')->label('Ikon'),
                TextColumn::make('label')->label('Nama Amal')->searchable()->sortable(),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'hitungan' ? 'Hitungan' : 'Centang')
                    ->color(fn (string $state) => $state === 'hitungan' ? 'info' : 'gray'),
                TextColumn::make('nilai_maks')->label('Maks')->placeholder('—'),
                TextColumn::make('satuan')->label('Satuan'),
                TextColumn::make('bobot')->label('Bobot')->sortable(),
                IconColumn::make('aktif')->label('Aktif')->boolean()->sortable(),
            ])
            ->defaultSort('urutan')
            ->recordActions([EditAction::make()]);
    }
}
