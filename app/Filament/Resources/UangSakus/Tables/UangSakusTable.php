<?php

namespace App\Filament\Resources\UangSakus\Tables;

use App\Enums\JenisUangSaku;
use App\Models\Santri;
use App\Models\UangSakuSantri;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UangSakusTable
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

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (UangSakuSantri $record): string => $record->jenis->color())
                    ->formatStateUsing(fn (UangSakuSantri $record): string => $record->jenis->label()),

                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->formatStateUsing(fn (int $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('—')
                    ->limit(40),

                TextColumn::make('pencatat.name')
                    ->label('Dicatat oleh')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('santri_id')
                    ->label('Santri')
                    ->options(function () {
                        return Santri::where('pesantren_id', auth()->user()?->pesantren_id)
                            ->orderBy('nama_lengkap')
                            ->pluck('nama_lengkap', 'id');
                    })
                    ->searchable(),

                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options(JenisUangSaku::options()),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->successNotificationTitle('Transaksi dihapus.'),
            ]);
    }
}
