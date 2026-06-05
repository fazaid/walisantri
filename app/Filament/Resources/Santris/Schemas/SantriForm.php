<?php

// File: app/Filament/Resources/Santris/Schemas/SantriForm.php

namespace App\Filament\Resources\Santris\Schemas;

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\User;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class SantriForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Santri')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nis')
                            ->label('NIS')
                            ->required()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),
                        TextInput::make('nama_lengkap')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(fn () => Kelas::where('pesantren_id', auth()->user()?->pesantren_id)
                                ->orderBy('nama_kelas')
                                ->pluck('nama_kelas', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false),
                        Select::make('kamar_id')
                            ->label('Kamar')
                            ->options(fn () => Kamar::where('pesantren_id', auth()->user()?->pesantren_id)
                                ->orderBy('nama_kamar')
                                ->pluck('nama_kamar', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false),
                        Toggle::make('status_aktif')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),

                Section::make('Relasi')
                    ->columns(2)
                    ->schema([
                        Select::make('wali_santri_id')
                            ->label('Wali Santri')
                            ->options(
                                User::where('role', 'wali_santri')
                                    ->where('pesantren_id', auth()->user()?->pesantren_id)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),
                        Select::make('pembimbing_ustadz_id')
                            ->label('Ustadz Pembimbing')
                            ->options(function () {
                                $counts = Santri::where('status_aktif', true)
                                    ->selectRaw('pembimbing_ustadz_id, COUNT(*) as total')
                                    ->groupBy('pembimbing_ustadz_id')
                                    ->pluck('total', 'pembimbing_ustadz_id');

                                return User::where('role', 'ustadz')
                                    ->where('pesantren_id', auth()->user()?->pesantren_id)
                                    ->get()
                                    ->mapWithKeys(fn ($u) => [
                                        $u->id => $u->name . ' (' . ($counts[$u->id] ?? 0) . '/20)',
                                    ]);
                            })
                            ->searchable()
                            ->required()
                            ->rules([
                                fn ($get, $record) => function (string $attribute, $value, $fail) use ($record) {
                                    $count = Santri::where('pembimbing_ustadz_id', $value)
                                        ->where('status_aktif', true)
                                        ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                        ->count();
                                    if ($count >= 20) {
                                        $fail('Ustadz ini sudah mencapai batas maksimal 20 santri.');
                                    }
                                },
                            ]),
                    ]),
            ]);
    }
}