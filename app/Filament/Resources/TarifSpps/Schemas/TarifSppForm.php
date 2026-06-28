<?php

namespace App\Filament\Resources\TarifSpps\Schemas;

use App\Models\Kelas;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TarifSppForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kelas_id')
                ->label('Kelas')
                ->options(function () {
                    return Kelas::where('pesantren_id', auth()->user()?->pesantren_id)
                        ->orderBy('nama_kelas')
                        ->pluck('nama_kelas', 'id');
                })
                ->searchable()
                ->required(),

            TextInput::make('nominal')
                ->label('Nominal SPP (Rp)')
                ->numeric()
                ->minValue(1)
                ->required(),

            TextInput::make('keterangan')
                ->label('Keterangan')
                ->nullable(),
        ]);
    }
}
