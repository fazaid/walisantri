<?php

namespace App\Filament\Resources\DemoRequests\Tables;

use App\Models\DemoRequest;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DemoRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('nama_pesantren')
                    ->label('Pesantren')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('kota')
                    ->label('Kota')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('nama_kontak')
                    ->label('Kontak')
                    ->searchable(),

                TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('jumlah_santri')
                    ->label('Jml. Santri')
                    ->placeholder('—'),

                IconColumn::make('contacted_at')
                    ->label('Sudah Dihubungi')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->getStateUsing(fn (DemoRequest $record): bool => $record->contacted_at !== null),

                TextColumn::make('created_at')
                    ->label('Daftar')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('contacted')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah dihubungi')
                    ->falseLabel('Belum dihubungi')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('contacted_at'),
                        false: fn ($query) => $query->whereNull('contacted_at'),
                    ),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),

                Action::make('tandai_dihubungi')
                    ->label('Tandai Dihubungi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DemoRequest $record): bool => $record->contacted_at === null)
                    ->requiresConfirmation()
                    ->modalHeading('Tandai sudah dihubungi?')
                    ->modalDescription('Waktu kontak akan dicatat sekarang.')
                    ->action(function (DemoRequest $record): void {
                        $record->update(['contacted_at' => now()]);

                        Notification::make()
                            ->title('Ditandai sudah dihubungi.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
