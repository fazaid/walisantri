<?php

namespace App\Filament\Resources\Pesantrens\Schemas;

use App\Enums\PaketLangganan;
use App\Enums\StatusBerlangganan;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PesantrenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_pesantren')
                    ->label('Nama Pesantren')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('Auto-generate dari nama pesantren, atau isi manual.'),

                Select::make('paket_langganan')
                    ->label('Paket Langganan')
                    ->options(PaketLangganan::options())
                    ->default(PaketLangganan::Rintisan->value)
                    ->required()
                    ->native(false),

                TextInput::make('max_santri_kuota')
                    ->label('Maks. Kuota Santri')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(100),

                Select::make('status_berlangganan')
                    ->label('Status Berlangganan')
                    ->options(StatusBerlangganan::options())
                    ->default(StatusBerlangganan::Trial->value)
                    ->required()
                    ->native(false),

                DateTimePicker::make('expired_at')
                    ->label('Tanggal Expired')
                    ->nullable()
                    ->seconds(false),
            ]);
    }
}
