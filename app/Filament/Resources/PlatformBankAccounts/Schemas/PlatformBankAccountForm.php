<?php

namespace App\Filament\Resources\PlatformBankAccounts\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlatformBankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('bank')
                ->label('Nama Bank')
                ->required()
                ->maxLength(50),
            TextInput::make('nomor_rekening')
                ->label('Nomor Rekening')
                ->required()
                ->maxLength(50),
            TextInput::make('atas_nama')
                ->label('Atas Nama')
                ->required()
                ->maxLength(100),
            FileUpload::make('logo')
                ->label('Logo Bank')
                ->disk('public')
                ->directory('bank-logos')
                ->image()
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                ->maxSize(512)
                ->previewable(false)
                ->nullable(),
            TextInput::make('urutan')
                ->label('Urutan Tampil')
                ->numeric()
                ->default(0)
                ->helperText('Angka lebih kecil tampil lebih dulu di halaman invoice.'),
            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true)
                ->helperText('Nonaktifkan untuk menyembunyikan dari halaman invoice tanpa menghapus data.'),
        ]);
    }
}
