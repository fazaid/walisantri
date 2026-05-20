<?php

// ============================================================
// FILE 5: app/Filament/Resources/MasterPengumumen/Schemas/MasterPengumumanInfolist.php
// ============================================================

namespace App\Filament\Resources\MasterPengumumen\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MasterPengumumanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengumuman')->schema([
                    TextEntry::make('judul_maklumat')->label('Judul'),
                    TextEntry::make('isi_maklumat')->label('Isi')->html(),
                    TextEntry::make('created_at')->label('Dipublikasikan')->dateTime('d M Y, H:i'),
                ]),
            ]);
    }
}