<?php

// ============================================================
// FILE 2: app/Filament/Resources/KesantrianMutabaahs/Schemas/KesantrianMutabaahInfolist.php
// ============================================================

namespace App\Filament\Resources\KesantrianMutabaahs\Schemas;

use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use App\Services\MutabaahScoreCalculator;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class KesantrianMutabaahInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Santri & Tanggal')->columns(2)->schema([
                    TextEntry::make('santri.nama_lengkap')->label('Santri'),
                    TextEntry::make('tanggal')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('skor')
                        ->label('Skor Amalan')
                        ->state(fn (KesantrianMutabaah $record) => MutabaahScoreCalculator::persentase($record).'%')
                        ->badge(),
                    TextEntry::make('status_udzur')->label('Status Udzur')
                        ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state))
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'Tidak'        => 'success',
                            'Sakit'        => 'danger',
                            'Haid'         => 'warning',
                            'Izin_Pulang'  => 'info',
                            'Tugas_Pondok' => 'info',
                            default        => 'gray',
                        }),
                ]),
                Section::make('Amalan Harian')->columns(3)->schema(self::amalanEntries()),
            ]);
    }

    protected static function amalanEntries(): array
    {
        $masterList = KesantrianAmalMaster::where('pesantren_id', Auth::user()?->pesantren_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();

        return $masterList->map(function (KesantrianAmalMaster $item) {
            $label = trim(($item->icon ? $item->icon.' ' : '').$item->label);

            if ($item->tipe === 'hitungan') {
                return TextEntry::make("amalan.{$item->kode}")
                    ->label($label)
                    ->suffix("/{$item->nilai_maks} {$item->satuan}");
            }

            return IconEntry::make("amalan.{$item->kode}")
                ->label($label)
                ->boolean();
        })->all();
    }
}
