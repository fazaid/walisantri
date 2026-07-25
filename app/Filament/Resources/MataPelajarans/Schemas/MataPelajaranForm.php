<?php

namespace App\Filament\Resources\MataPelajarans\Schemas;

use App\Models\Kelas;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class MataPelajaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kelas_id')
                ->label('Kelas')
                ->options(
                    Kelas::where('pesantren_id', auth()->user()?->pesantren_id)
                        ->pluck('nama_kelas', 'id')
                )
                ->searchable()
                ->required(),
            Select::make('ustadz_id')
                ->label('Ustadz Pengampu')
                ->options(
                    User::where('role', 'ustadz')
                        ->where('pesantren_id', auth()->user()?->pesantren_id)
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->nullable(),
            TextInput::make('nama_mapel')
                ->label('Nama Mata Pelajaran')
                ->required()
                ->maxLength(100)
                ->unique(
                    table: 'mata_pelajaran',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                        ->where('pesantren_id', auth()->user()?->pesantren_id)
                        ->where('kelas_id', $get('kelas_id')),
                )
                ->validationMessages([
                    'unique' => 'Mata pelajaran dengan nama ini sudah ada di kelas tersebut.',
                ]),
        ]);
    }
}
