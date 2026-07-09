<?php

namespace App\Filament\Resources\MasterPengumumen\Tables;

use App\Enums\UserRole;
use App\Models\Pesantren;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

                TextColumn::make('pesantren.nama_pesantren')
                    ->label('Pesantren')
                    ->badge()
                    ->color(fn ($record): string => $record->pesantren_id === null ? 'gray' : 'info')
                    ->formatStateUsing(fn ($state, $record): string => $record->pesantren_id === null ? 'Global (Semua Pesantren)' : $state)
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
            ->filters([
                SelectFilter::make('pesantren_id')
                    ->label('Pesantren')
                    ->options(fn () => Pesantren::orderBy('nama_pesantren')->pluck('nama_pesantren', 'id'))
                    ->searchable()
                    ->visible(fn (): bool => auth()->user()?->role === UserRole::SuperAdmin->value),

                TernaryFilter::make('is_global')
                    ->label('Cakupan')
                    ->placeholder('Semua')
                    ->trueLabel('Global (Semua Pesantren)')
                    ->falseLabel('Pesantren Tertentu')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('pesantren_id'),
                        false: fn (Builder $query) => $query->whereNotNull('pesantren_id'),
                    )
                    ->visible(fn (): bool => auth()->user()?->role === UserRole::SuperAdmin->value),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->authorizeIndividualRecords('delete')])]);
    }
}
