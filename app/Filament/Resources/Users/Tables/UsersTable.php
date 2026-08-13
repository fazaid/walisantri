<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\PenugasanUstadz;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => UserRole::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->label() ?? $state)
                    ->sortable(),

                TextColumn::make('penugasan')
                    ->label('Penugasan')
                    ->state(fn (User $record): array => PenugasanUstadz::ringkasan($record))
                    ->badge()
                    ->color('success')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('pesantren.nama_pesantren')
                    ->label('Pesantren')
                    ->searchable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Filter Role')
                    ->options(UserRole::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->mutateDataUsing(UserResource::paksaTenantSaatUbah()),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
