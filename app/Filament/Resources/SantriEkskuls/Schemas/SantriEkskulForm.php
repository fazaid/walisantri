<?php

namespace App\Filament\Resources\SantriEkskuls\Schemas;

use App\Enums\UserRole;
use App\Support\Waktu;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

class SantriEkskulForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Keikutsertaan')
                ->columns(2)
                ->schema([
                    Select::make('santri_id')
                        ->label('Santri')
                        // relationship() menghormati global scope Multitenantable,
                        // jadi tidak perlu filter pesantren_id manual. Tanpa
                        // preload(): pencarian dilakukan di server, bukan dengan
                        // memuat seluruh tabel santri ke dalam payload Livewire.
                        ->relationship(
                            'santri',
                            'nama_lengkap',
                            function (Builder $query) {
                                $query->where('status_aktif', true)
                                    ->orderBy('nama_lengkap');

                                // Ustadz hanya boleh mendaftarkan santri
                                // bimbingannya sendiri — selaras dengan
                                // ScopesQueryToUstadzSantri di resource.
                                if (Auth::user()?->role === UserRole::Ustadz->value) {
                                    $query->where('pembimbing_ustadz_id', Auth::id());
                                }
                            },
                        )
                        ->searchable()
                        ->required(),
                    Select::make('ekskul_id')
                        ->label('Ekskul')
                        ->relationship(
                            'ekskulMaster',
                            'nama',
                            fn (Builder $query) => $query->where('aktif', true)->orderBy('nama'),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->unique(
                            table: 'santri_ekskuls',
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                                ->where('santri_id', $get('santri_id')),
                        )
                        ->validationMessages([
                            'unique' => 'Santri ini sudah terdaftar di ekskul tersebut.',
                        ]),
                    Select::make('level')
                        ->label('Level')
                        ->options([
                            'pemula'   => 'Pemula',
                            'menengah' => 'Menengah',
                            'mahir'    => 'Mahir',
                        ])
                        ->default('pemula')
                        ->required(),
                    DatePicker::make('tanggal_mulai')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->maxDate(Waktu::akhirHariIni())
                        ->native(false),
                    Toggle::make('aktif')
                        ->label('Aktif')
                        ->default(true)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
