<?php

namespace App\Filament\Widgets;

use App\Enums\PaketLangganan;
use App\Enums\StatusBerlangganan;
use App\Enums\UserRole;
use App\Models\Pesantren;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TenantListWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Semua Pesantren')
            ->query(Pesantren::withoutGlobalScope('pesantren'))
            ->columns([
                TextColumn::make('nama_pesantren')
                    ->label('Nama Pesantren')
                    ->searchable()
                    ->sortable(),

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
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspend')
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->requiresConfirmation()
                    ->modalHeading('Suspend Pesantren?')
                    ->modalDescription('Akses semua user pesantren ini akan diblokir.')
                    ->action(fn (Pesantren $record) => $record->update(['status_berlangganan' => StatusBerlangganan::Suspended->value]))
                    ->visible(fn (Pesantren $record): bool => in_array($record->status_berlangganan, [
                        StatusBerlangganan::Active->value,
                        StatusBerlangganan::Trial->value,
                    ])),

                Action::make('aktifkan')
                    ->label('Aktifkan')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Aktifkan Pesantren?')
                    ->modalDescription('Status berlangganan akan diubah menjadi aktif.')
                    ->action(fn (Pesantren $record) => $record->update(['status_berlangganan' => StatusBerlangganan::Active->value]))
                    ->visible(fn (Pesantren $record): bool => in_array($record->status_berlangganan, [
                        StatusBerlangganan::Suspended->value,
                        StatusBerlangganan::Expired->value,
                    ])),
            ]);
    }
}
