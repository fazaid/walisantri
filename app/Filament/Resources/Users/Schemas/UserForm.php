<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\Pesantren;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('phone_number')
                    ->label('No. Telepon')
                    ->tel()
                    ->nullable()
                    ->maxLength(20),

                Select::make('role')
                    ->label('Role')
                    ->options(UserRole::options())
                    ->default(UserRole::WaliSantri->value)
                    ->required()
                    ->native(false),

                Select::make('pesantren_id')
                    ->label('Pesantren')
                    ->options(fn () => Pesantren::pluck('nama_pesantren', 'id'))
                    ->searchable()
                    ->nullable()
                    ->placeholder('Kosongkan untuk Super Admin'),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->maxLength(255)
                    ->hiddenOn('edit'),
            ]);
    }
}
