<?php

namespace App\Filament\Resources\KesantrianMutabaahRapors\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class KesantrianMutabaahRaporInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas')->columns(4)->schema([
                TextEntry::make('santri.nama_lengkap')->label('Santri'),
                TextEntry::make('nama_bulan')->label('Bulan'),
                TextEntry::make('tahun')->label('Tahun'),
                TextEntry::make('total_hari_input')->label('Total Hari Tercatat'),
                TextEntry::make('total_hari_udzur')->label('Hari Udzur'),
                TextEntry::make('rata_rata_persen')
                    ->label('Rata-rata Kepatuhan')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'warning',
                        default      => 'danger',
                    }),
            ]),

            Section::make('Rekap Udzur')
                ->hidden(fn ($record) => empty($record->udzur_detail))
                ->schema([
                    TextEntry::make('udzur_detail')
                        ->label('')
                        ->formatStateUsing(function ($state) {
                            if (empty($state)) return '-';
                            $label = [
                                'Sakit'        => 'Sakit',
                                'Haid'         => 'Haid',
                                'Izin_Pulang'  => 'Izin Pulang',
                                'Tugas_Pondok' => 'Tugas Pondok',
                            ];
                            return collect($state)
                                ->map(fn ($jumlah, $kode) => ($label[$kode] ?? $kode) . ': ' . $jumlah . ' hari')
                                ->join(' | ');
                        }),
                ]),

            Section::make('Ringkasan Amalan')
                ->schema(function ($record): array {
                    $ringkasan = $record->ringkasan_amalan ?? [];
                    if (empty($ringkasan)) {
                        return [TextEntry::make('_empty')->label('')->default('Tidak ada data amalan.')];
                    }

                    return collect($ringkasan)->map(function (array $item, string $kode) {
                        $label  = trim(($item['icon'] ?? '') . ' ' . ($item['label'] ?? $kode));
                        $persen = $item['persen'] ?? 0;

                        if (($item['tipe'] ?? '') === 'hitungan') {
                            $detail = "{$item['total_capai']}/{$item['total_maks']} ({$persen}%)";
                        } else {
                            $detail = "{$item['total_capai']}/{$item['total_maks']} hari ({$persen}%)";
                        }

                        return TextEntry::make("ringkasan_amalan.{$kode}.persen")
                            ->label($label)
                            ->formatStateUsing(fn () => $detail)
                            ->badge()
                            ->color(fn () => match (true) {
                                $persen >= 80 => 'success',
                                $persen >= 60 => 'warning',
                                default       => 'danger',
                            });
                    })->values()->all();
                })
                ->columns(3),

            Section::make('Catatan')
                ->hidden(fn ($record) => blank($record->catatan))
                ->schema([
                    TextEntry::make('catatan')->label('')->placeholder('-'),
                ]),
        ]);
    }
}
