<?php

// File: app/Filament/Resources/TahfidzProgress/Tables/TahfidzProgressTable.php

namespace App\Filament\Resources\TahfidzProgress\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TahfidzProgressTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('santri.nama_lengkap')
                    ->label('Santri')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipe_setoran')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sabaq'  => 'success',
                        'Sabqi'  => 'info',
                        'Manzil' => 'warning',
                    }),
                TextColumn::make('nama_surah')
                    ->label('Surah')
                    ->searchable(),
                TextColumn::make('ayat_mulai')
                    ->label('Ayat')
                    ->formatStateUsing(fn ($record): string =>
                        $record->ayat_mulai . ' - ' . $record->ayat_selesai
                    ),
                TextColumn::make('nilai_kelancaran')
                    ->label('Nilai')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mumtaz'        => 'success',
                        'Jayyid Jiddan' => 'info',
                        'Jayyid'        => 'warning',
                        'Maqbul'        => 'danger',
                    }),
                TextColumn::make('ustadz.name')
                    ->label('Ustadz')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('tipe_setoran')
                    ->label('Tipe Setoran')
                    ->options([
                        'Sabaq'  => 'Sabaq',
                        'Sabqi'  => 'Sabqi',
                        'Manzil' => 'Manzil',
                    ]),
                SelectFilter::make('nilai_kelancaran')
                    ->label('Nilai')
                    ->options([
                        'Mumtaz'        => 'Mumtaz',
                        'Jayyid Jiddan' => 'Jayyid Jiddan',
                        'Jayyid'        => 'Jayyid',
                        'Maqbul'        => 'Maqbul',
                    ]),
                SelectFilter::make('santri')
                    ->label('Santri')
                    ->relationship('santri', 'nama_lengkap')
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}