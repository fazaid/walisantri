<?php

namespace App\Filament\Resources\DemoRequests\Pages;

use App\Filament\Resources\DemoRequests\DemoRequestResource;
use App\Filament\Resources\DemoRequests\Schemas\DemoRequestInfolist;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewDemoRequest extends ViewRecord
{
    protected static string $resource = DemoRequestResource::class;

    public function infolist(Schema $schema): Schema
    {
        return DemoRequestInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tandai_dihubungi')
                ->label('Tandai Sudah Dihubungi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->contacted_at === null)
                ->requiresConfirmation()
                ->modalHeading('Tandai sudah dihubungi?')
                ->modalDescription('Waktu kontak akan dicatat sekarang.')
                ->action(function (): void {
                    $this->record->update(['contacted_at' => now()]);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Ditandai sudah dihubungi.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
