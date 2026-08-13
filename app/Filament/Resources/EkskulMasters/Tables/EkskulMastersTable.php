<?php

namespace App\Filament\Resources\EkskulMasters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EkskulMastersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Ekskul')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pembina')
                    ->label('Pembina')
                    ->state(fn ($record): ?string => $record->namaPembina())
                    ->placeholder('— belum diisi —'),
                // Nama kolom WAJIB sama dengan atribut hasil withCount, yaitu
                // Str::snake(namaRelasi).'_count' — 'santriEkskuls_count' (camelCase)
                // tidak pernah ada isinya sehingga kolom Peserta selalu kosong.
                TextColumn::make('santri_ekskuls_count')
                    ->label('Peserta')
                    ->counts('santriEkskuls')
                    ->badge()
                    ->color('info'),
                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nama', 'asc')
            ->filters([
                TernaryFilter::make('aktif')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->modalWidth(Width::Medium),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
