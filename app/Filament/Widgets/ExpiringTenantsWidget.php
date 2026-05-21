<?php

namespace App\Filament\Widgets;

use App\Enums\PaketLangganan;
use App\Enums\StatusBerlangganan;
use App\Enums\UserRole;
use App\Models\Pesantren;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ExpiringTenantsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pesantren Akan Expired (7 Hari)')
            ->query(
                Pesantren::withoutGlobalScope('pesantren')
                    ->whereBetween('expired_at', [now(), now()->addDays(7)])
                    ->orderBy('expired_at')
            )
            ->columns([
                TextColumn::make('nama_pesantren')
                    ->label('Nama Pesantren')
                    ->searchable(),

                TextColumn::make('paket_langganan')
                    ->label('Paket')
                    ->badge()
                    ->color(fn (string $state): string => PaketLangganan::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => PaketLangganan::tryFrom($state)?->label() ?? $state),

                TextColumn::make('expired_at')
                    ->label('Expired')
                    ->dateTime('d M Y HH:mm')
                    ->sortable(),

                TextColumn::make('status_berlangganan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => StatusBerlangganan::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => StatusBerlangganan::tryFrom($state)?->label() ?? $state),
            ]);
    }
}
