<?php

namespace App\Filament\Resources\Kamars\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class KamarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama_kamar')
                ->label('Nama Kamar')
                ->required()
                ->maxLength(100)
                ->unique(
                    table: 'kamar',
                    column: 'nama_kamar',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule) => $rule->where('pesantren_id', auth()->user()?->pesantren_id)
                ),

            // Musyrif = penugasan, bukan role. Satu kamar satu musyrif; satu ustadz
            // boleh memusyrifi beberapa kamar, jadi tanpa batas kuota seperti aturan
            // 20 santri untuk pembimbing. Label saja — tidak membuka akses data apa
            // pun atas santri penghuninya (§5.4).
            //
            // Sengaja ->options() dan bukan ->relationship(): User tidak memakai trait
            // Multitenantable, jadi saringan tenant harus manual.
            Select::make('musyrif_id')
                ->label('Musyrif')
                ->options(fn () => User::where('role', UserRole::Ustadz->value)
                    ->where('pesantren_id', auth()->user()?->pesantren_id)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->placeholder('Belum ditentukan'),

            TextInput::make('kapasitas')
                ->label('Kapasitas')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->helperText('Isi 0 jika tidak ada batas kapasitas'),
        ]);
    }
}
