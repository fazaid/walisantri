<?php

namespace App\Filament\Resources\Kelas\Schemas;

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
        ]);
    }
}
