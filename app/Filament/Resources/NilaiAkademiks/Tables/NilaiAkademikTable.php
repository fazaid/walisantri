<?php

namespace App\Filament\Resources\NilaiAkademiks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NilaiAkademikTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('santri.nama_lengkap')
                    ->label('Santri')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mataPelajaran.nama_mapel')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->sortable(),
                TextColumn::make('periode')
                    ->label('Periode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Bulanan'         => 'info',
                        'Semester_Ganjil' => 'warning',
                        'Semester_Genap'  => 'success',
                        default           => 'gray',
                    }),
                TextColumn::make('bulan')
                    ->label('Bulan')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nilai')
                    ->label('Nilai')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 85 => 'success',
                        $state >= 70 => 'info',
                        $state >= 60 => 'warning',
                        default      => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Diinput')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('periode')
                    ->label('Periode')
                    ->options([
                        'Bulanan'         => 'Bulanan',
                        'Semester_Ganjil' => 'Semester Ganjil',
                        'Semester_Genap'  => 'Semester Genap',
                    ]),
                SelectFilter::make('mataPelajaran')
                    ->label('Mata Pelajaran')
                    ->relationship('mataPelajaran', 'nama_mapel')
                    ->searchable(),
                SelectFilter::make('santri')
                    ->label('Santri')
                    ->relationship('santri', 'nama_lengkap')
                    ->searchable(),
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
