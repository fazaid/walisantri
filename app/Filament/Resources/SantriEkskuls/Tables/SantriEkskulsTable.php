<?php

namespace App\Filament\Resources\SantriEkskuls\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SantriEkskulsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('santri.nama_lengkap')
                    ->label('Santri')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ekskulMaster.nama')
                    ->label('Ekskul')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pemula'   => 'warning',
                        'menengah' => 'info',
                        'mahir'    => 'success',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pemula'   => 'Pemula',
                        'menengah' => 'Menengah',
                        'mahir'    => 'Mahir',
                        default    => ucfirst($state),
                    }),
                TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('ekskulMaster')
                    ->label('Ekskul')
                    ->relationship('ekskulMaster', 'nama')
                    ->searchable(),
                SelectFilter::make('level')
                    ->label('Level')
                    ->options([
                        'pemula'   => 'Pemula',
                        'menengah' => 'Menengah',
                        'mahir'    => 'Mahir',
                    ]),
                TernaryFilter::make('aktif')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
