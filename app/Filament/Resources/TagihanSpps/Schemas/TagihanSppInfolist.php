<?php

namespace App\Filament\Resources\TagihanSpps\Schemas;

use App\Models\TagihanSpp;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TagihanSppInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Tagihan')
                ->columns(2)
                ->schema([
                    TextEntry::make('santri.nama_lengkap')->label('Nama Santri'),
                    TextEntry::make('bulan')
                        ->label('Periode')
                        ->formatStateUsing(fn (TagihanSpp $record): string => $record->label_periode),
                    TextEntry::make('nominal')
                        ->label('Nominal')
                        ->formatStateUsing(fn (int $state): string => 'Rp ' . number_format($state, 0, ',', '.')),
                    TextEntry::make('jatuh_tempo')->label('Jatuh Tempo')->date('d M Y')->placeholder('—'),
                    TextEntry::make('keterangan')->label('Keterangan')->placeholder('—'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (TagihanSpp $record): string => $record->status->color())
                        ->formatStateUsing(fn (TagihanSpp $record): string => $record->status->label()),
                ]),

            Section::make('Pembayaran')
                ->columns(2)
                ->schema([
                    TextEntry::make('pembayaran.tanggal_bayar')
                        ->label('Tanggal Bayar')
                        ->date('d M Y')
                        ->placeholder('Belum dibayar'),
                    TextEntry::make('pembayaran.metode_bayar')
                        ->label('Metode')
                        ->formatStateUsing(fn (?string $state): string =>
                            \App\Models\PembayaranSpp::$metodeBayar[$state] ?? '—'
                        )
                        ->placeholder('—'),
                    TextEntry::make('pembayaran.jumlah')
                        ->label('Jumlah Dibayar')
                        ->formatStateUsing(fn (?int $state): string =>
                            $state ? 'Rp ' . number_format($state, 0, ',', '.') : '—'
                        ),
                    TextEntry::make('pembayaran.dicatatOleh.name')
                        ->label('Dicatat Oleh')
                        ->placeholder('—'),
                    TextEntry::make('pembayaran.catatan')
                        ->label('Catatan')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
