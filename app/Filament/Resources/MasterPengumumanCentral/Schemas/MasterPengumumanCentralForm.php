<?php

namespace App\Filament\Resources\MasterPengumumanCentral\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MasterPengumumanCentralForm
{
    public static function configure(Schema $schema): Schema
    {
        // ListRecords memaksa schema modal jadi 2 kolom kalau form tidak
        // menentukan sendiri. columns(1) menahannya supaya tiap field penuh
        // selebar modal — form ini pendek, jadi tanpa Section pembungkus:
        // judulnya cuma mengulang judul modal dan bikin kartu di dalam kartu.
        return $schema
            ->columns(1)
            ->components([
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
            ]);
    }
}
