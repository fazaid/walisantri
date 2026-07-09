<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\Pesantren;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->required(fn (Get $get) => $get('role') !== UserRole::WaliSantri->value)
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? $state : null)
                    ->helperText(fn (Get $get) => $get('role') === UserRole::WaliSantri->value
                        ? 'Boleh dikosongkan kalau wali cuma punya nomor WhatsApp — magic link portal wali tetap bisa dipakai tanpa email.'
                        : null),

                TextInput::make('phone_number')
                    ->label('No. Telepon')
                    ->tel()
                    ->nullable()
                    ->maxLength(20),

                Select::make('role')
                    ->label('Role')
                    ->options(function () {
                        $options = UserRole::options();
                        if (auth()->user()?->role === UserRole::AdminPesantren->value) {
                            unset($options[UserRole::SuperAdmin->value]);
                        }

                        return $options;
                    })
                    ->default(fn (): string => UserRole::tryFrom((string) request()->query('role'))?->value
                        ?? UserRole::WaliSantri->value)
                    ->required()
                    ->live()
                    ->native(false),

                Select::make('pesantren_id')
                    ->label('Pesantren')
                    ->options(fn () => Pesantren::pluck('nama_pesantren', 'id'))
                    ->searchable()
                    ->nullable()
                    ->placeholder('Kosongkan untuk Super Admin')
                    ->hidden(fn () => auth()->user()?->role === UserRole::AdminPesantren->value),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->maxLength(255)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): string => $operation === 'edit'
                        ? 'Kosongkan jika tidak ingin mengubah password.'
                        : ''),

                TextInput::make('password_confirmation')
                    ->label('Konfirmasi Password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->maxLength(255)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false)
                    ->same('password')
                    ->helperText(fn (string $operation): string => $operation === 'edit'
                        ? 'Kosongkan jika tidak ingin mengubah password.'
                        : ''),
            ]);
    }
}
