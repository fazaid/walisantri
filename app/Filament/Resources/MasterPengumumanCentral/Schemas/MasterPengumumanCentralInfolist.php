<?php

namespace App\Filament\Resources\MasterPengumumanCentral\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MasterPengumumanCentralInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengumuman Central')->schema([
                    TextEntry::make('judul_maklumat')
                        ->label('Judul Pengumuman'),

                    TextEntry::make('isi_maklumat')
                        ->label('Isi Pengumuman')
                        ->html(),

                    IconEntry::make('is_active')
                        ->label('Status')
                        ->boolean(),

                    TextEntry::make('created_at')
                        ->label('Dipublikasikan')
                        ->dateTime('d M Y, H:i'),
                ]),
            ]);
    }
}
