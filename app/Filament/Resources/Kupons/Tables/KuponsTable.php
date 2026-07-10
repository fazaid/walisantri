<?php

namespace App\Filament\Resources\Kupons\Tables;

use App\Enums\TipeDiskon;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class KuponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                TextColumn::make('tipe_diskon')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (TipeDiskon $state) => $state->label()),

                TextColumn::make('nilai_diskon')
                    ->label('Nilai')
                    ->formatStateUsing(function ($record) {
                        if ($record->tipe_diskon === TipeDiskon::Nominal) {
                            return 'Rp ' . number_format($record->nilai_diskon, 0, ',', '.');
                        }
                        return $record->nilai_diskon . '%';
                    }),

                TextColumn::make('jumlah_dipakai')
                    ->label('Dipakai')
                    ->formatStateUsing(fn ($record) =>
                        $record->max_penggunaan
                            ? "{$record->jumlah_dipakai} / {$record->max_penggunaan}"
                            : "{$record->jumlah_dipakai} / ∞"
                    ),

                TextColumn::make('berlaku_hingga')
                    ->label('Berlaku hingga')
                    ->dateTime('d M Y')
                    ->placeholder('Tidak terbatas')
                    ->color(fn ($record) =>
                        $record->berlaku_hingga?->isPast() ? 'danger' : 'success'
                    ),

                IconColumn::make('is_aktif')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_aktif')->label('Status'),
                SelectFilter::make('tipe_diskon')
                    ->label('Tipe Diskon')
                    ->options(TipeDiskon::options()),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
