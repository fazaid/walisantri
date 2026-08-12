<?php

namespace App\Filament\Resources\SantriEkskuls\Schemas;

use App\Models\SantriEkskul;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SantriEkskulInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Keikutsertaan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('santri.nama_lengkap')
                            ->label('Santri'),
                        TextEntry::make('ekskulMaster.nama')
                            ->label('Ekskul'),
                        TextEntry::make('ekskulMaster.pengajar')
                            ->label('Pembina')
                            ->placeholder('— belum diisi —'),
                        TextEntry::make('level')
                            ->label('Level')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pemula' => 'warning',
                                'menengah' => 'info',
                                'mahir' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (SantriEkskul $record): string => $record->labelLevel()),
                        TextEntry::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->date('d M Y'),
                        IconEntry::make('aktif')
                            ->label('Aktif')
                            ->boolean(),
                    ]),
            ]);
    }
}
