<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\UpgradeOrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('konfirmasi_langsung')
                ->label('Konfirmasi Langsung')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->visible(fn (): bool => $this->record->isPendingPayment())
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Tanpa Bukti Transfer')
                ->modalDescription('Gunakan ini jika pembayaran sudah diterima secara langsung (tunai, transfer via konfirmasi verbal, dll). Paket pesantren akan langsung diupgrade.')
                ->form([
                    Textarea::make('catatan_admin')
                        ->label('Catatan (wajib — catat metode/bukti pembayaran)')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(UpgradeOrderService::class)->confirmOrder(
                        $this->record,
                        Auth::user(),
                        $data['catatan_admin'],
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title('Order dikonfirmasi langsung!')
                        ->success()
                        ->send();
                }),

            Action::make('konfirmasi')
                ->label('Konfirmasi Order')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->isAwaitingConfirmation())
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Order')
                ->modalDescription('Paket pesantren akan diupgrade setelah dikonfirmasi.')
                ->form([
                    Textarea::make('catatan_admin')
                        ->label('Catatan (opsional)')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(UpgradeOrderService::class)->confirmOrder(
                        $this->record,
                        Auth::user(),
                        $data['catatan_admin'] ?? null,
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title('Order dikonfirmasi!')
                        ->success()
                        ->send();
                }),

            Action::make('tolak')
                ->label('Tolak Order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->isAwaitingConfirmation())
                ->requiresConfirmation()
                ->form([
                    Textarea::make('catatan_admin')
                        ->label('Alasan penolakan')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(UpgradeOrderService::class)->rejectOrder(
                        $this->record,
                        Auth::user(),
                        $data['catatan_admin'],
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title('Order ditolak.')
                        ->warning()
                        ->send();
                }),
        ];
    }
}
