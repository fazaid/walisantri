<?php

namespace App\Filament\Resources\Pesantrens\Schemas;

use App\Enums\PaketLangganan;
use App\Enums\StatusBerlangganan;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PesantrenInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nama_pesantren')
                    ->label('Nama Pesantren'),

                TextEntry::make('slug')
                    ->label('Slug'),

                TextEntry::make('paket_langganan')
                    ->label('Paket Langganan')
                    ->badge()
                    ->color(fn (string $state): string => PaketLangganan::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => PaketLangganan::tryFrom($state)?->label() ?? $state),

                TextEntry::make('status_berlangganan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => StatusBerlangganan::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => StatusBerlangganan::tryFrom($state)?->label() ?? $state),

                TextEntry::make('max_santri_kuota')
                    ->label('Maks. Santri'),

                TextEntry::make('expired_at')
                    ->label('Expired')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('-'),
            ]);
    }
}
