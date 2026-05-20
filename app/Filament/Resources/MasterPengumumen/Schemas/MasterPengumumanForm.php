<?php

// ============================================================
// PENGUMUMAN
// FILE 4: app/Filament/Resources/MasterPengumumen/Schemas/MasterPengumumanForm.php
// ============================================================

namespace App\Filament\Resources\MasterPengumumen\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MasterPengumumanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengumuman')->schema([
                    TextInput::make('judul_maklumat')
                        ->label('Judul Pengumuman')
                        ->required()->maxLength(255),
                    RichEditor::make('isi_maklumat')
                        ->label('Isi Pengumuman')
                        ->required()
                        ->toolbarButtons(['bold','italic','underline','bulletList','orderedList','h2','h3']),
                ]),
            ]);
    }
}