<?php

namespace App\Filament\Resources\Kupons\Schemas;

use App\Enums\TipeDiskon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class KuponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kode & Diskon')->columns(2)->schema([
                TextInput::make('kode')
                    ->label('Kode Kupon')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash()
                    ->maxLength(32)
                    ->hint('Hanya huruf, angka, dan tanda hubung')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('kode', strtoupper(trim($state ?? ''))))
                    ->dehydrateStateUsing(fn (?string $state) => strtoupper(trim($state ?? '')))
                    ->columnSpan(1),

                Select::make('tipe_diskon')
                    ->label('Tipe Diskon')
                    ->options(TipeDiskon::options())
                    ->required()
                    ->reactive()
                    ->columnSpan(1),

                TextInput::make('nilai_diskon')
                    ->label('Nilai Diskon')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(fn ($get) => $get('tipe_diskon') === TipeDiskon::Persentase->value ? 100 : null)
                    ->required()
                    ->prefix(fn ($get) => $get('tipe_diskon') === TipeDiskon::Nominal->value ? 'Rp' : null)
                    ->suffix(fn ($get) => $get('tipe_diskon') === TipeDiskon::Persentase->value ? '%' : null)
                    ->columnSpan(2),
            ]),

            Section::make('Batasan Penggunaan')->columns(2)->schema([
                TextInput::make('min_durasi_bulan')
                    ->label('Minimal durasi berlangganan')
                    ->numeric()->minValue(1)->nullable()
                    ->suffix('bulan')
                    ->helperText('Kosongkan jika tidak ada syarat minimum'),

                TextInput::make('max_penggunaan')
                    ->label('Maksimal penggunaan total')
                    ->numeric()->minValue(1)->nullable()
                    ->helperText('Kosongkan untuk tidak terbatas'),

                DateTimePicker::make('berlaku_hingga')
                    ->label('Berlaku hingga')
                    ->nullable()
                    ->helperText('Kosongkan jika tidak ada batas waktu'),

                Toggle::make('is_aktif')
                    ->label('Kupon aktif')
                    ->default(true),
            ]),

            Section::make('Catatan')->schema([
                Textarea::make('catatan')
                    ->label('Catatan internal')
                    ->rows(2)
                    ->nullable(),
            ]),
        ]);
    }
}
