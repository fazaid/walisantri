<?php

namespace App\Filament\Resources\PresensiJamPelajarans\Tables;

use App\Filament\Resources\PresensiJamPelajarans\PresensiJamPelajaranResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PresensiJamPelajaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jam_ke')
                    ->label('Jam ke-')
                    ->sortable(),

                TextColumn::make('jam_mulai')
                    ->label('Mulai')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),

                TextColumn::make('jam_selesai')
                    ->label('Selesai')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),

                TextColumn::make('label')->label('Label')->placeholder('—'),

                IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->defaultSort('jam_ke', 'asc')
            // Delapan baris, tanpa paginasi: daftar ini pendek dan dibaca sebagai
            // satu jadwal utuh — memotongnya jadi halaman justru menyulitkan.
            ->paginated(false)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record): bool => PresensiJamPelajaranResource::canDelete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => PresensiJamPelajaranResource::canDeleteAny()),
                ]),
            ]);
    }
}
