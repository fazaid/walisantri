<?php

namespace App\Filament\Resources\MataPelajarans\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class MataPelajaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // relationship() sudah tersaring global scope Multitenantable,
            // jadi filter pesantren_id manual tidak diperlukan lagi.
            Select::make('kelas_id')
                ->label('Kelas')
                ->relationship(
                    'kelas',
                    'nama_kelas',
                    fn (Builder $query) => $query->orderBy('nama_kelas'),
                )
                ->searchable()
                ->preload()
                ->required(),
            Select::make('ustadz_id')
                ->label('Ustadz Pengampu')
                ->relationship(
                    'ustadz',
                    'name',
                    fn (Builder $query) => $query
                        ->where('role', UserRole::Ustadz->value)
                        ->orderBy('name'),
                )
                ->searchable()
                ->preload()
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
