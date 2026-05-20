<?php

// File: app/Filament/Resources/Santris/Schemas/SantriInfolist.php

namespace App\Filament\Resources\Santris\Schemas;

use App\Models\Santri;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SantriInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Santri')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nis')
                            ->label('NIS'),
                        TextEntry::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->columnSpanFull(),
                        TextEntry::make('kelas')
                            ->label('Kelas'),
                        TextEntry::make('kamar')
                            ->label('Kamar'),
                        IconEntry::make('status_aktif')
                            ->label('Status Aktif')
                            ->boolean(),
                        TextEntry::make('uuid')
                            ->label('Magic Link UUID')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Relasi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('wali.name')
                            ->label('Wali Santri'),
                        TextEntry::make('pembimbing.name')
                            ->label('Ustadz Pembimbing'),
                        TextEntry::make('pesantren.nama_pesantren')
                            ->label('Pesantren'),
                    ]),

                Section::make('Timestamps')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('deleted_at')
                            ->label('Dihapus')
                            ->dateTime('d M Y, H:i')
                            ->visible(fn (Santri $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}