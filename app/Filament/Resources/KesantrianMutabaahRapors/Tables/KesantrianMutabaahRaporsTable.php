<?php

namespace App\Filament\Resources\KesantrianMutabaahRapors\Tables;

use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KesantrianMutabaahRaporsTable
{
    public static function configure(Table $table): Table
    {
        $tahunSekarang = (int) date('Y');
        $tahunOptions  = [];
        for ($y = $tahunSekarang; $y >= $tahunSekarang - 3; $y--) {
            $tahunOptions[(string) $y] = (string) $y;
        }

        return $table
            ->columns([
                TextColumn::make('santri.nama_lengkap')
                    ->label('Santri')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_bulan')
                    ->label('Bulan')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('bulan', $direction)),

                TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),

                TextColumn::make('total_hari_input')
                    ->label('Hari Tercatat')
                    ->alignCenter(),

                TextColumn::make('total_hari_udzur')
                    ->label('Hari Udzur')
                    ->alignCenter(),

                TextColumn::make('rata_rata_persen')
                    ->label('Rata-rata')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'warning',
                        default      => 'danger',
                    })
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tahun', 'desc')
            ->filters([
                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options($tahunOptions),

                SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
