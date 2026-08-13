<?php

namespace App\Filament\Resources\EkskulMasters\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EkskulMasterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Ekskul')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nama')
                            ->label('Nama Ekskul'),
                        TextEntry::make('pembina')
                            ->label('Pembina')
                            ->state(fn ($record): ?string => $record->namaPembina())
                            ->placeholder('— belum diisi —'),
                        IconEntry::make('aktif')
                            ->label('Aktif')
                            ->boolean(),
                        TextEntry::make('santri_ekskuls_count')
                            ->label('Jumlah Peserta')
                            ->state(fn ($record): int => $record->santriEkskuls()->count())
                            ->badge()
                            ->color('info'),
                    ]),

                Section::make('Deskripsi')
                    ->columns(1)
                    ->schema([
                        // Terisi lewat form sejak awal tapi belum pernah
                        // ditampilkan di mana pun.
                        TextEntry::make('deskripsi')
                            ->hiddenLabel()
                            ->placeholder('Tidak ada deskripsi'),
                    ]),
            ]);
    }
}
