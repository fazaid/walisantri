<?php

namespace App\Filament\Resources\DemoRequests\Tables;

use App\Models\DemoRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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

                IconColumn::make('duplicate_of_id')
                    ->label('Duplikat?')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->getStateUsing(fn (DemoRequest $record): bool => $record->duplicate_of_id !== null),

                IconColumn::make('contacted_at')
                    ->label('Sudah Dihubungi')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->getStateUsing(fn (DemoRequest $record): bool => $record->contacted_at !== null),

                TextColumn::make('sla')
                    ->label('SLA')
                    ->badge()
                    ->state(fn (DemoRequest $record): string => match (true) {
                        $record->contacted_at !== null => 'Selesai',
                        $record->isOverdue() => 'Overdue',
                        default => $record->businessDaysWaiting().' hr kerja',
                    })
                    ->color(fn (DemoRequest $record): string => match (true) {
                        $record->contacted_at !== null => 'success',
                        $record->isOverdue() => 'danger',
                        default => 'gray',
                    }),

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

                Filter::make('overdue')
                    ->label('Overdue (SLA)')
                    ->query(fn ($query) => $query->overdue()),

                TernaryFilter::make('duplicate')
                    ->label('Duplikat')
                    ->placeholder('Semua')
                    ->trueLabel('Kemungkinan duplikat')
                    ->falseLabel('Bukan duplikat')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('duplicate_of_id'),
                        false: fn ($query) => $query->whereNull('duplicate_of_id'),
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

                DeleteAction::make()->label('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
