<?php

namespace App\Filament\Resources\MataPelajarans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MataPelajaranInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Mata Pelajaran')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nama_mapel')
                            ->label('Nama Mata Pelajaran'),
                        TextEntry::make('kelas.nama_kelas')
                            ->label('Kelas'),
                        TextEntry::make('ustadz.name')
                            ->label('Ustadz Pengampu')
                            ->placeholder('— belum ditugaskan —'),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i'),
                    ]),
            ]);
    }
}
