<?php

namespace App\Filament\Resources\MataPelajarans\Schemas;

use App\Models\Kelas;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                ->maxLength(100),
        ]);
    }
}
