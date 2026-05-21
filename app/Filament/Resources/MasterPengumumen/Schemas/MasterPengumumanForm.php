<?php

namespace App\Filament\Resources\MasterPengumumen\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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
                        ->required()
                        ->maxLength(255),

                    RichEditor::make('isi_maklumat')
                        ->label('Isi Pengumuman')
                        ->required()
                        ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'h2', 'h3']),

                    Select::make('target_audience')
                        ->label('Tampilkan Kepada')
                        ->options([
                            'semua' => '👥 Semua Pengguna',
                            'admin' => '🏫 Admin & Ustadz Pesantren',
                            'wali'  => '👨‍👩‍👧 Wali Santri',
                        ])
                        ->default('semua')
                        ->required(),
                ]),
            ]);
    }
}
