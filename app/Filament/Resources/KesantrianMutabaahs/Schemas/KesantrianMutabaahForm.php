<?php

namespace App\Filament\Resources\KesantrianMutabaahs\Schemas;

use App\Filament\Support\SantriOptions;
use App\Models\KesantrianAmalMaster;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class KesantrianMutabaahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Santri & Tanggal')
                    ->columns(2)
                    ->schema([
                        Select::make('santri_id')
                            ->label('Santri')
                            ->options(fn () => SantriOptions::aktifUntukPengguna())
                            ->searchable()->required(),
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(now())->required(),
                        Select::make('status_udzur')
                            ->label('Status Udzur')
                            ->options([
                                'Tidak' => 'Tidak',
                                'Sakit' => 'Sakit',
                                'Haid' => 'Haid',
                                'Izin_Pulang' => 'Izin Pulang',
                                'Tugas_Pondok' => 'Tugas Pondok',
                            ])->default('Tidak')->required(),
                    ]),

                Section::make('Amalan Harian')
                    ->columns(2)
                    ->schema(self::amalanFields()),
            ]);
    }

    protected static function amalanFields(): array
    {
        $masterList = KesantrianAmalMaster::where('pesantren_id', Auth::user()?->pesantren_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();

        return $masterList->map(function (KesantrianAmalMaster $item) {
            $label = trim(($item->icon ? $item->icon.' ' : '').$item->label);

            if ($item->tipe === 'hitungan') {
                return TextInput::make("amalan.{$item->kode}")
                    ->label("{$label} (dari {$item->nilai_maks})")
                    ->numeric()
                    ->minValue(0)
                    ->maxValue($item->nilai_maks)
                    ->default($item->nilai_maks)
                    ->required();
            }

            return Toggle::make("amalan.{$item->kode}")
                ->label($label)
                ->default(false);
        })->all();
    }
}
