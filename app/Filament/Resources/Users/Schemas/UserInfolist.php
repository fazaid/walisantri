<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PenugasanUstadz;
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

                // Penugasan tidak disimpan di kolom mana pun — seluruhnya turunan
                // dari FK (santri.pembimbing_ustadz_id, kelas.wali_kelas_id,
                // mata_pelajaran.ustadz_id, ekskul_masters.pembina_id), jadi tidak
                // bisa basi dan tidak menambah nilai baru ke users.role.
                TextEntry::make('penugasan')
                    ->label('Penugasan')
                    ->state(fn (User $record): array => PenugasanUstadz::ringkasan($record))
                    ->badge()
                    ->color('success')
                    ->placeholder('Belum ada penugasan')
                    ->visible(fn (User $record): bool => $record->role === UserRole::Ustadz->value),

                TextEntry::make('pesantren.nama_pesantren')
                    ->label('Pesantren')
                    ->placeholder('-'),
            ]);
    }
}
