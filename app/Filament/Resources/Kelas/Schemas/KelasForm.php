<?php

namespace App\Filament\Resources\Kelas\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class KelasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama_kelas')
                ->label('Nama Kelas')
                ->required()
                ->maxLength(100)
                ->unique(
                    table: 'kelas',
                    column: 'nama_kelas',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule) => $rule->where('pesantren_id', auth()->user()?->pesantren_id)
                ),

            // Wali kelas = penugasan, bukan role. Satu kelas satu wali; satu ustadz
            // boleh mewalikan beberapa kelas, jadi tanpa batas kuota seperti aturan
            // 20 santri untuk pembimbing.
            Select::make('wali_kelas_id')
                ->label('Wali Kelas')
                ->options(fn () => User::where('role', UserRole::Ustadz->value)
                    ->where('pesantren_id', auth()->user()?->pesantren_id)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->placeholder('Belum ditentukan'),
        ]);
    }
}
