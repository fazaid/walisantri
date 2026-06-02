<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\PaketLangganan;
use App\Enums\StatusOrder;
use App\Models\Order;
use App\Services\UpgradeOrderService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('nomor_order')
                    ->label('Nomor Order')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('pesantren.nama_pesantren')
                    ->label('Pesantren')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('paket_target')
                    ->label('Paket')
                    ->badge()
                    ->color(fn (Order $record): string => $record->paket_target->color())
                    ->formatStateUsing(fn (Order $record): string => $record->paket_target->label())
                    ->sortable(),

                TextColumn::make('durasi_total_bulan')
                    ->label('Durasi')
                    ->formatStateUsing(fn (int $state): string => $state . ' bulan')
                    ->sortable(),

                TextColumn::make('harga_total')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Order $record): string => $record->status->color())
                    ->formatStateUsing(fn (Order $record): string => $record->status->label())
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusOrder::options()),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('konfirmasi')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->isAwaitingConfirmation())
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Order')
                    ->modalDescription('Paket pesantren akan diupgrade setelah dikonfirmasi.')
                    ->form([
                        Textarea::make('catatan_admin')
                            ->label('Catatan (opsional)')
                            ->rows(3),
                    ])
                    ->action(function (Order $record, array $data): void {
                        app(UpgradeOrderService::class)->confirmOrder(
                            $record,
                            Auth::user(),
                            $data['catatan_admin'] ?? null,
                        );

                        Notification::make()
                            ->title('Order dikonfirmasi!')
                            ->body("Paket {$record->pesantren->nama_pesantren} berhasil diupgrade.")
                            ->success()
                            ->send();
                    }),

                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => $record->isAwaitingConfirmation())
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Order')
                    ->form([
                        Textarea::make('catatan_admin')
                            ->label('Alasan penolakan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Order $record, array $data): void {
                        app(UpgradeOrderService::class)->rejectOrder(
                            $record,
                            Auth::user(),
                            $data['catatan_admin'],
                        );

                        Notification::make()
                            ->title('Order ditolak.')
                            ->warning()
                            ->send();
                    }),
            ]);
    }
}
