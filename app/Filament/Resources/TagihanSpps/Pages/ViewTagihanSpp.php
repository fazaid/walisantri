<?php

namespace App\Filament\Resources\TagihanSpps\Pages;

use App\Enums\StatusTagihanSpp;
use App\Filament\Resources\TagihanSpps\TagihanSppResource;
use App\Filament\Resources\TagihanSpps\Schemas\TagihanSppInfolist;
use App\Models\PembayaranSpp;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewTagihanSpp extends ViewRecord
{
    protected static string $resource = TagihanSppResource::class;

    public function infolist(Schema $schema): Schema
    {
        return TagihanSppInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tandai_lunas')
                ->label('Tandai Lunas')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => ! $this->record->isLunas())
                ->form([
                    DatePicker::make('tanggal_bayar')
                        ->label('Tanggal Bayar')
                        ->default(now())
                        ->required(),
                    Select::make('metode_bayar')
                        ->label('Metode Bayar')
                        ->options(PembayaranSpp::$metodeBayar)
                        ->default('tunai')
                        ->required(),
                    TextInput::make('catatan')
                        ->label('Catatan (opsional)'),
                ])
                ->action(function (array $data): void {
                    PembayaranSpp::create([
                        'pesantren_id'   => $this->record->pesantren_id,
                        'tagihan_spp_id' => $this->record->id,
                        'jumlah'         => $this->record->nominal,
                        'tanggal_bayar'  => $data['tanggal_bayar'],
                        'metode_bayar'   => $data['metode_bayar'],
                        'catatan'        => $data['catatan'] ?? null,
                        'dicatat_oleh'   => auth()->id(),
                    ]);

                    $this->record->update(['status' => StatusTagihanSpp::Lunas]);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Tagihan ditandai lunas.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
