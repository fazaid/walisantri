<?php

namespace App\Filament\Resources\Pesantrens\Schemas;

use App\Enums\PaketLangganan;
use App\Enums\StatusBerlangganan;
use App\Models\Pesantren;
use App\Rules\SlugNotReserved;
use App\Rules\ValidTenantSlug;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
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

                // Aturan yang sama persis dengan jalur pendaftaran publik
                // (RegisterController). Tanpa ini super admin bisa membuat slug
                // "admin" (bentrok dengan path panel), slug berspasi (hostname tidak
                // valid), atau slug yang baru dilepas tenant lain — menembus cooldown
                // 90 hari sehingga tautan lama mengarah ke tenant yang keliru.
                //
                // Tapi HANYA saat slug benar-benar dibuat/diubah. Panel dulu
                // mengizinkan sampai 255 karakter, jadi kalau aturan ini dipasang
                // tanpa syarat, pesantren lama yang slug-nya terlanjur panjang tidak
                // bisa diedit sama sekali — paket, kuota, dan tanggal expired-nya ikut
                // tersandera oleh field yang bahkan tidak disentuh.
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->rules(
                        [new ValidTenantSlug, new SlugNotReserved],
                        condition: fn (?Pesantren $record, ?string $state): bool => $record === null || $state !== $record->slug,
                    )
                    ->helperText('3-30 karakter, huruf kecil dan angka. Auto-generate dari nama pesantren, atau isi manual.'),

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
