<?php

namespace App\Filament\Resources\EkskulMasters\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
            // Dua jalur pembina yang sengaja hidup berdampingan: ustadz internal
            // ditaut ke akunnya (ikut tampil di daftar penugasan), pelatih luar
            // yang tidak punya akun cukup ditulis namanya.
            Select::make('pembina_id')
                ->label('Pembina (Ustadz)')
                ->options(fn () => User::where('role', UserRole::Ustadz->value)
                    ->where('pesantren_id', auth()->user()?->pesantren_id)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->placeholder('Belum ditentukan'),
            TextInput::make('pengajar')
                ->label('Pembina Luar')
                ->helperText('Untuk pelatih dari luar pesantren yang tidak punya akun.')
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
