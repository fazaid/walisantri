<?php

// File: app/Filament/Resources/Santris/Schemas/SantriForm.php

namespace App\Filament\Resources\Santris\Schemas;

use App\Enums\JenisKelamin;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\User;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                            ->unique(
                                table: 'santri',
                                column: 'nis',
                                modifyRuleUsing: fn ($rule) => $rule->where('pesantren_id', auth()->user()?->pesantren_id),
                                ignoreRecord: true,
                            ),
                        TextInput::make('nama_lengkap')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('nama_panggilan')
                            ->maxLength(100),
                        DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->maxDate(now()),
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::options())
                            ->native(false),
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

                Section::make('Biodata')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_ayah')
                            ->maxLength(255),
                        TextInput::make('nama_ibu')
                            ->maxLength(255),
                        Textarea::make('alamat_lengkap')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('jumlah_saudara')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('cita_cita')
                            ->maxLength(255),
                        Textarea::make('ciri_fisik')
                            ->label('Ciri Fisik yang Mudah Dikenali')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Foto Profil')
                    ->schema([
                        FileUpload::make('foto_profil')
                            ->label('Foto Profil')
                            ->disk('public')
                            ->directory('foto-profil')
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->maxSize(2048)
                            ->nullable(),
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
                            ->nullable(),
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
                            ->nullable()
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