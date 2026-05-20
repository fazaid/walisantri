<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nama'),

                TextEntry::make('email')
                    ->label('Email'),

                TextEntry::make('phone_number')
                    ->label('No. Telepon')
                    ->placeholder('-'),

                TextEntry::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => UserRole::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->label() ?? $state),

                TextEntry::make('pesantren.nama_pesantren')
                    ->label('Pesantren')
                    ->placeholder('-'),
            ]);
    }
}
