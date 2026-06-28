<?php

namespace App\Filament\Resources\TarifSpps\Tables;

use App\Models\TarifSpp;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TarifSppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->formatStateUsing(fn (int $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('—'),
            ])
            ->defaultSort('kelas.nama_kelas')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
