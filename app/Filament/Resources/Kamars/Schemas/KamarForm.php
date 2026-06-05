<?php

namespace App\Filament\Resources\Kamars\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KamarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama_kamar')
                ->label('Nama Kamar')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),
            TextInput::make('kapasitas')
                ->label('Kapasitas')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->helperText('Isi 0 jika tidak ada batas kapasitas'),
        ]);
    }
}
