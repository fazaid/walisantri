<?php

namespace App\Filament\Resources\PrestasiSantris\Schemas;

use App\Models\PrestasiSantri;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrestasiSantriInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Prestasi')
                ->columns(2)
                ->schema([
                    TextEntry::make('santri.nama_lengkap')->label('Santri'),
                    TextEntry::make('tanggal')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('judul')->label('Judul / Nama Lomba')->columnSpanFull(),
                    TextEntry::make('kategori')->label('Kategori')->badge()->color('info'),
                    TextEntry::make('tingkat')
                        ->label('Tingkat')
                        ->badge()
                        ->color(fn (PrestasiSantri $record): string => $record->tingkat->color())
                        ->formatStateUsing(fn (PrestasiSantri $record): string => $record->tingkat->label()),
                    TextEntry::make('posisi')->label('Posisi')->placeholder('—'),
                    TextEntry::make('penyelenggara')->label('Penyelenggara')->placeholder('—'),
                    TextEntry::make('keterangan')->label('Keterangan')->placeholder('—')->columnSpanFull(),
                ]),

            Section::make('Dokumen')
                ->schema([
                    ImageEntry::make('dokumen')
                        ->label('Sertifikat / Foto')
                        ->disk('public')
                        ->height(300)
                        ->placeholder('Tidak ada dokumen'),
                ]),
        ]);
    }
}
