<?php

namespace App\Filament\Resources\MasterPengumumanCentral\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MasterPengumumanCentralForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengumuman Central')->schema([
                    TextInput::make('judul_maklumat')
                        ->label('Judul Pengumuman')
                        ->required()
                        ->maxLength(255),

                    RichEditor::make('isi_maklumat')
                        ->label('Isi Pengumuman')
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'link',
                            'bulletList', 'orderedList', 'h2', 'h3',
                        ]),

                    Toggle::make('is_active')
                        ->label('Aktif & Tampilkan')
                        ->default(true),
                ]),
            ]);
    }
}
