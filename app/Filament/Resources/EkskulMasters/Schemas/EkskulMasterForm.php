<?php

namespace App\Filament\Resources\EkskulMasters\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EkskulMasterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama Ekskul')
                ->required()
                ->maxLength(100),
            TextInput::make('pengajar')
                ->label('Nama Pembina')
                ->nullable()
                ->maxLength(100),
            Textarea::make('deskripsi')
                ->label('Deskripsi')
                ->nullable()
                ->rows(3),
            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
        ]);
    }
}
