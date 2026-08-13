<?php

namespace App\Filament\Resources\TarifSpps\Schemas;

use App\Models\Kelas;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class TarifSppForm
{
    public static function configure(Schema $schema): Schema
    {
        // ->columns(1) wajib: ListRecords memaksa schema modal jadi 2 kolom,
        // tanpa ini field-field pendek berjejer setengah lebar.
        return $schema->columns(1)->components([
            Select::make('kelas_id')
                ->label('Kelas')
                ->options(function () {
                    return Kelas::where('pesantren_id', auth()->user()?->pesantren_id)
                        ->orderBy('nama_kelas')
                        ->pluck('nama_kelas', 'id');
                })
                ->searchable()
                ->required()
                ->unique(
                    table: 'tarif_spp',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule) => $rule
                        ->where('pesantren_id', auth()->user()?->pesantren_id),
                )
                ->validationMessages([
                    'unique' => 'Kelas ini sudah memiliki tarif SPP. Silakan edit tarif yang ada, bukan membuat baru.',
                ]),

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
