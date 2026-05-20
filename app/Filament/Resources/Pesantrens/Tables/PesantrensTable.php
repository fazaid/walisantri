<?php

namespace App\Filament\Resources\Pesantrens\Tables;

use App\Enums\PaketLangganan;
use App\Enums\StatusBerlangganan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PesantrensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_pesantren')
                    ->label('Nama Pesantren')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),

                TextColumn::make('paket_langganan')
                    ->label('Paket')
                    ->badge()
                    ->color(fn (string $state): string => PaketLangganan::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => PaketLangganan::tryFrom($state)?->label() ?? $state)
                    ->sortable(),

                TextColumn::make('status_berlangganan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => StatusBerlangganan::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => StatusBerlangganan::tryFrom($state)?->label() ?? $state)
                    ->sortable(),

                TextColumn::make('max_santri_kuota')
                    ->label('Maks. Santri')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('expired_at')
                    ->label('Expired')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                //
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
