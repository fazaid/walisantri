<?php

namespace App\Filament\Resources\PlatformBankAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PlatformBankAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->square(),
                TextColumn::make('bank')
                    ->label('Bank')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nomor_rekening')
                    ->label('Nomor Rekening')
                    ->searchable(),
                TextColumn::make('atas_nama')
                    ->label('Atas Nama')
                    ->searchable(),
                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->sortable(),
                ToggleColumn::make('aktif')
                    ->label('Aktif'),
            ])
            ->defaultSort('urutan', 'asc')
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
