<?php

namespace App\Filament\Resources\MasterPengumumen\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MasterPengumumenTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('judul_maklumat')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('target_audience')
                    ->label('Kepada')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'semua' => 'success',
                        'admin' => 'info',
                        'wali'  => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'semua' => 'Semua',
                        'admin' => 'Admin & Ustadz',
                        'wali'  => 'Wali Santri',
                        default => $state,
                    }),

                TextColumn::make('isi_maklumat')
                    ->label('Isi')
                    ->html()
                    ->limit(80)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dipublikasikan')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
